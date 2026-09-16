using System;
using System.Drawing;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.IO;

namespace GraalViewer2
{
    public class TexLoader
    {
        static Dictionary<string, SFML.Graphics.Image> imageLook = new Dictionary<string, SFML.Graphics.Image>();
        static Dictionary<string, bool> needsUpdate = new Dictionary<string, bool>();

        public static void removeImage(string n)
        {
            if (!imageLook.ContainsKey(n))
            {
                return;
            }

            imageLook.Remove(n);
        }

        public static SFML.Graphics.Image getImage(string n)
        {
            if (imageLook.ContainsKey(n))
            {
                return imageLook[n];
            }

            if (File.Exists(n))
            {
                try
                {
                    SFML.Graphics.Image nI = new SFML.Graphics.Image(n);
                    nI.Smooth = false;

                    imageLook.Add(n, nI);

                    return nI;
                }

                catch (Exception e)
                {
                    Console.WriteLine("ERROR LOADING IMAGE: " + e.Message);
                    return null;
                }
            }

            return null;
        }
    }
}
