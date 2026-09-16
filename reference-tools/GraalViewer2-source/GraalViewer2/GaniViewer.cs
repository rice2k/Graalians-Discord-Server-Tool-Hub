using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Drawing;
using System.Data;
using System.Linq;
using System.Text;
using System.Windows.Forms;
using System.IO;
using SFML.Window;
using SFML.Graphics;

namespace GraalViewer2
{
    public partial class GaniViewer : UserControl
    {
        public SFML.Graphics.RenderWindow rWindow;
        public Gani currentGani;

        public GVWindow parentWindow;

        public String currentHead = "head0.png";
        public String currentBody = "body.png";
        public String currentShield = "shield1.png";
        public String currentSword = "sword1.png";
        public String currentHat = "hat0.png";
        FileSystemWatcher[] _watchers;

        int dragStartX;
        int dragStartY;
        int dragStartEX;
        int dragStartEY;
        int currentViewX;
        int currentViewY;
        bool isDragging = false;

        public GaniViewer()
        {
            InitializeComponent();

            currentGani = new Gani();
            currentGani.loadFromFile("ganis/iidle.gani");
        }

        public void init()
        {Cursor.Current = Cursors.SizeAll;
            try
            {
                rWindow = new SFML.Graphics.RenderWindow(this.Handle);
                rWindow.SetSize((uint)this.Width, (uint)this.Height);
                rWindow.CurrentView.SetFromRect(new SFML.Graphics.FloatRect(0, 0, this.Width, this.Height));
                rWindow.DefaultView.SetFromRect(new SFML.Graphics.FloatRect(0, 0, this.Width, this.Height));
            }

            catch (Exception e)
            {
                string t = e.Message;
            }

            currentViewX = -this.Width / 2 + 24;
            currentViewY = -this.Height / 2 + 24;

            string[] drives = Environment.GetLogicalDrives();
            _watchers = new FileSystemWatcher[drives.Length];

            int i = 0;
            foreach (string strDrive in drives)
            {
                FileSystemWatcher _watcher = new FileSystemWatcher();

                try
                {
                    _watcher.Path = strDrive;
                    _watcher.Changed += new FileSystemEventHandler(fileWatcher_fileChanged);
                    _watcher.IncludeSubdirectories = true;
                    _watcher.EnableRaisingEvents = true;
                    _watchers[i] = _watcher;

                    i++;
                }

                catch
                {
                    _watchers[i] = null;
                }
            }
        }

        private void fileWatcher_fileChanged(object sender, System.IO.FileSystemEventArgs e)
        {
            try
            {
                string[] toks_a = e.FullPath.Split(new char[] { '\\', '/' });

                string[] toks_b = currentHead.Split(new char[] { '\\', '/' });
                if (toks_a[toks_a.Length - 1] == toks_b[toks_b.Length - 1])
                {
                    TexLoader.removeImage(currentHead);
                    Console.WriteLine("Refreshed head image.");
                }

                toks_b = currentBody.Split(new char[] { '\\', '/' });
                if (toks_a[toks_a.Length - 1] == toks_b[toks_b.Length - 1])
                {
                    TexLoader.removeImage(currentBody);
                    Console.WriteLine("Refreshed body image.");
                }

                toks_b = currentSword.Split(new char[] { '\\', '/' });
                if (toks_a[toks_a.Length - 1] == toks_b[toks_b.Length - 1])
                {
                    TexLoader.removeImage(currentSword);
                    Console.WriteLine("Refreshed sword image.");
                }

                toks_b = currentHat.Split(new char[] { '\\', '/' });
                if (toks_a[toks_a.Length - 1] == toks_b[toks_b.Length - 1])
                {
                    TexLoader.removeImage(currentHat);
                    Console.WriteLine("Refreshed hat image.");
                }

                toks_b = currentShield.Split(new char[] { '\\', '/' });
                if (toks_a[toks_a.Length - 1] == toks_b[toks_b.Length - 1])
                {
                    TexLoader.removeImage(currentShield);
                    Console.WriteLine("Refreshed hat image.");
                }
            }

            catch (Exception ex)
            {
            }
        }

        public void render()
        {
            rWindow.Clear(new SFML.Graphics.Color(240, 240, 240));

            if (currentGani != null)
                currentGani.draw(-currentViewX, -currentViewY, parentWindow.dirBox.SelectedIndex, this);

            rWindow.Display();
        }

        private void gv_dragDrop(object sender, DragEventArgs e)
        {
            string[] files = (string[])e.Data.GetData(DataFormats.FileDrop);
            foreach (string file in files)
            {
                string[] toks = file.Split(new char[]{'.'});
                string fileType = "";
                try
                {
                    fileType = toks[1];
                }

                catch (Exception ex)
                {
                    return;
                }

                if (fileType == "gani")
                {
                    bool oldPlaying = false;
                    if (currentGani != null) {
                        oldPlaying = currentGani.playing;
                    }
                    currentGani = new Gani();
                    currentGani.loadFromFile(file);
                    currentGani.playing = oldPlaying;
                }
                else if (fileType == "png" || fileType == "gif")
                {
                    SFML.Graphics.Image rImg = TexLoader.getImage(file);

                    if (rImg == null) return;

                    if (rImg.Width == 128 && rImg.Height == 720) //Body
                    {
                        TexLoader.removeImage(currentBody);
                        currentBody = file;
                        Console.WriteLine("Changed body image.");
                    }
                    else if (rImg.Width == 32 && rImg.Height == 557) //Head
                    {
                        TexLoader.removeImage(currentHead);
                        currentHead = file;
                        Console.WriteLine("Changed head image.");
                    }
                    else if (rImg.Width == 128 && rImg.Height == 96) //Sword
                    {
                        TexLoader.removeImage(currentSword);
                        currentSword = file;
                        Console.WriteLine("Changed sword image.");
                    }
                    else if (rImg.Width == 192 && rImg.Height == 144) //Hat
                    {
                        TexLoader.removeImage(currentHat);
                        currentHat = file;
                        Console.WriteLine("Changed hat image.");
                    }
                    else //Must be a shield.. Graal's fucking stupid.
                    {
                        TexLoader.removeImage(currentShield);
                        currentShield = file;
                        Console.WriteLine("Changed shield image.");
                    }
                }
            }
        }

        private void gv_dragEnter(object sender, DragEventArgs e)
        {
            if (e.Data.GetDataPresent(DataFormats.FileDrop)) e.Effect = DragDropEffects.Copy;
        }

        private void gv_mouseDown(object sender, MouseEventArgs e)
        {
            dragStartX = e.X;
            dragStartY = e.Y;
            dragStartEX = currentViewX;
            dragStartEY = currentViewY;
            isDragging = true;
        }

        private void gv_mouseMove(object sender, MouseEventArgs e)
        {
            if (!isDragging) return;
            currentViewX = dragStartEX + dragStartX - e.X;
            currentViewY = dragStartEY + dragStartY - e.Y;
            Cursor.Current = Cursors.SizeAll;
        }

        private void gv_mouseLeave(object sender, EventArgs e)
        {
            Cursor.Current = Cursors.Default;
            isDragging = false;
        }

        private void gv_mouseUp(object sender, MouseEventArgs e)
        {
            Cursor.Current = Cursors.Default;
            isDragging = false;
        }
    }
}
