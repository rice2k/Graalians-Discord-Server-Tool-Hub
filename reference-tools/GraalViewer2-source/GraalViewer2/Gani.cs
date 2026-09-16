using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Collections;
using System.IO;
using System.Text.RegularExpressions;
using System.Windows.Forms;
using SFML.Window;
using SFML.Graphics;

namespace GraalViewer2
{
    public class stageSprite
    {
        public int id;
        public int x;
        public int y;
    }

    public class spriteDef
    {
        public String type;
        public String file;
        public int index;
        public int px;
        public int py;
        public int w;
        public int h;
    }

    public class dirFrame
    {
        public List<stageSprite> sprites = new List<stageSprite>();
    }

    public class frame
    {
        public dirFrame[] dirFrames = new dirFrame[4];
    }

    public class Gani
    {
        public List<frame> frames = new List<frame>();
        public List<spriteDef> spriteDefs = new List<spriteDef>();
        public List<int> waits = new List<int>();
        public int currentFrame;
        int currentWait;
        bool singleDirection = false;

        public bool playing = false;

        public bool errord = false;

        public static String nextLine(Stack<string> lines)
        {
            if (lines.Count > 0)
            {
                String line = lines.Pop();
                line.Replace("\n", "");
                line.Replace("\r", "");

                return line;
            }
            else
            {
                return "";
            }
        }

        public spriteDef getSpriteDef(int id)
        {
            foreach (spriteDef s in spriteDefs)
            {
                if (s.index == id) return s;
            }

            return null;
        }

        public bool isAbsolutePath(string p) {
            if (p.IndexOf(':') != -1)
            {
                return true;
            }

            return false;
        }

        public string makePath(string p, string top)
        {
            return (isAbsolutePath(p)) ? p : top + "/" + p;
        }

        public void draw(int x, int y, int dir, GaniViewer targetWindow, bool dontIncrement = false)
        {
            if (errord) return;

            if (singleDirection) dir = 0;

            if (dir == 4)
            {
                for (int i = 0; i < 4; i++)
                {
                    draw(x, y + 90 * i, i, targetWindow, i > 0);
                }
                return;
            } else if (dir == 5)
            {
                for (int i = 0; i < 4; i++)
                {
                    draw(x + 90 * i, y, i, targetWindow, i > 0);
                }
                return;
            }

            if (!dontIncrement && playing)
            {
                if (currentWait < waits[currentFrame])
                {
                    currentWait++;
                }
                else
                {
                    currentWait = 0;
                    currentFrame++;
                }
            }

            if (currentFrame >= frames.Count) currentFrame = 0;
            if (currentFrame < 0) currentFrame = frames.Count - 1;

            SFML.Graphics.RenderWindow target = targetWindow.rWindow;

            Sprite d = new Sprite();

            foreach (stageSprite s in frames[currentFrame].dirFrames[dir].sprites)
            {
                spriteDef def = getSpriteDef(s.id);

                string fileLoc = "";

                if (def.type == "HEAD")
                {
                    fileLoc = makePath(targetWindow.currentHead, "heads");
                }
                else if (def.type == "BODY")
                {
                    fileLoc = makePath(targetWindow.currentBody, "bodies");
                }
                else if (def.type == "ATTR1")
                {
                    if (!targetWindow.parentWindow.hatCheckbox.Checked) continue;
                    fileLoc = makePath(targetWindow.currentHat, "images");
                }
                else if (def.type == "SWORD")
                {
                    fileLoc = makePath(targetWindow.currentSword, "swords");
                }
                else if (def.type == "SHIELD")
                {
                    fileLoc = makePath(targetWindow.currentShield, "shields");
                }
                else if (def.type == "SPRITES")
                {
                    fileLoc = makePath("sprites.png", "images");
                }
                else
                {
                    fileLoc = makePath(def.type, "images");
                }

                d.Image = TexLoader.getImage(fileLoc);

                if (def.type != "SHIELD")
                {
                    d.Position = new Vector2(x + s.x, y + s.y);
                    d.SubRect = new IntRect(def.px, def.py, def.px + def.w, def.py + def.h);
                }
                else
                {
                    float ratio = (float)d.Image.Width / 38.0f;
                    float ratioy = (float)d.Image.Height / 20.0f;

                    spriteDef rDef = new spriteDef();
                    rDef.px = (int)Math.Ceiling(def.px * ratio);
                    rDef.py = (int)Math.Ceiling(def.py * ratioy);
                    rDef.w = (int)Math.Ceiling(def.w * ratio);
                    rDef.h = (int)Math.Ceiling(def.h * ratioy);

                    d.Position = new Vector2(x + s.x - (rDef.w - def.w) / 2.0f, y + s.y - (rDef.h - def.h) / 2.0f);

                    d.SubRect = new IntRect(rDef.px, rDef.py, rDef.px + rDef.w, rDef.py + rDef.h);
                }

                target.Draw(d);
            }

            d.Dispose();
        }

        public void loadFromFile(String path)
        {
            Console.WriteLine("Loading animation from " + path + "...");

            Stack<string> revLines = new Stack<string>(System.IO.File.ReadAllLines(path));
            Stack<string> lines = new Stack<string>();
            foreach (string l in revLines)
            {
                lines.Push(l);
            }

            String line = nextLine(lines);
            while (lines.Count > 0)
            {
                line = line.Trim();
                line = Regex.Replace(line, @"[ ]{2,}", @" ");

                string[] words = line.Split(new char[]{' '});
                if (words.Count() == 0)
                {
                    continue;
                }

                switch (words[0])
                {
                    case "SINGLEDIRECTION":
                    {
                        singleDirection = true;
                        break;
                    }

                    case "SPRITE":
                    {
                        spriteDef s = new spriteDef();
                        s.index = int.Parse(words[1]);
                        s.type = words[2];
                        s.px = int.Parse(words[3]);
                        s.py = int.Parse(words[4]);
                        s.w = int.Parse(words[5]);
                        s.h = int.Parse(words[6]);
                        spriteDefs.Add(s);
                        break;
                    }

                    case "SCRIPT":
                    {
                        while (line != "SCRIPTEND")
                        {
                            line = nextLine(lines);
                        }
                        break;
                    }

                    case "ANI":
                    {
                        line = nextLine(lines);

                        while (line != "ANIEND") {
                            if (line.Length == 0)
                            {
                                line = nextLine(lines);
                                continue;
                            }

                            if (line.Split(' ')[0] == "PLAYSOUND")
                            {
                                line = nextLine(lines);
                                continue;
                            }

                            frame newFrame = new frame();
                            for (int dir = 0; dir < ((singleDirection) ? 1 : 4); dir++)
                            {
                                string[] offsets = line.Split(new char[] { ',' });

                                newFrame.dirFrames[dir] = new dirFrame();
                                foreach (string offsetO in offsets)
                                {
                                    string offset = offsetO.Trim();
                                    offset = Regex.Replace(offset, @"[ ]{2,}", @" ");

                                    string[] partsO = offset.Split(new char[] { ' ' });
                                    string[] parts = new string[3];
                                    int c = 0;
                                    foreach (string p in partsO)
                                    {
                                        if (IsNumeric(p))
                                        {
                                            parts[c++] = p;
                                        }
                                    }

                                    stageSprite newStageSprite = new stageSprite();

                                    newStageSprite.id = int.Parse(parts[0]);
                                    newStageSprite.x = int.Parse(parts[1]);
                                    newStageSprite.y = int.Parse(parts[2]);

                                    newFrame.dirFrames[dir].sprites.Add(newStageSprite);
                                }

                                if (dir < ((singleDirection) ? 0 : 3))
                                {
                                    line = nextLine(lines);
                                }
                            }

                            frames.Add(newFrame);
                            waits.Add(0);

                            line = nextLine(lines);
                            line = line.Trim();
                            line = Regex.Replace(line, @"[ ]{2,}", @" ");

                            while (true)
                            {
                                string[] toks = line.Split(' ');
                                if (toks[0] == "WAIT")
                                {
                                    waits[frames.Count - 1] = int.Parse(toks[1]);
                                }
                                if (line == "ANIEND")
                                {
                                    break;
                                }
                                else if (line.Length == 0 || line == "\n" || line == "\r" || !IsNumeric(toks[0]))
                                {
                                    line = nextLine(lines);
                                    line = line.Trim();
                                    line = Regex.Replace(line, @"[ ]{2,}", @" ");
                                } else {
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
                
                line = nextLine(lines);
            }

            Console.WriteLine("Success.");
        }

        public static System.Boolean IsNumeric (System.Object Expression)
        {
            if (Expression == null || Expression is DateTime)
                return false;

            if (Expression is Int16 || Expression is Int32 || Expression is Int64 || Expression is Decimal || Expression is Single || Expression is Double || Expression is Boolean)
                return true;
   
            try 
            {
                if (Expression is string)
                    Double.Parse(Expression as string);
                else
                    Double.Parse(Expression.ToString());
                return true;
            }

            catch { // just dismiss errors but return false
                return false;
            }

            return false;
        }
    }
}
