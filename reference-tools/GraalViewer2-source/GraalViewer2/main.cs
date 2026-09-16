using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Diagnostics;
using System.Windows.Forms;

namespace GraalViewer2
{
    class main
    {
        static GVWindow gw;

        [STAThread]
        public static void Main()
        {
            Console.WriteLine("Graal Animation Viewer 1.0 by Downsider");

            gw = new GVWindow();

            gw.Show();

            while (true)
            {
                gw.tick();
                Application.DoEvents();
                if (gw.done) return;
                System.Threading.Thread.Sleep(50);
            }
        }
    }
}
