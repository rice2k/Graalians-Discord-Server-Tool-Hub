using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Windows.Forms;
using System.IO;
using SFML.Window;
using SFML.Graphics;

namespace GraalViewer2
{
    public partial class GVWindow : Form
    {
        public GaniViewer ganiView;
        public bool done = false;

        public GVWindow()
        {
            InitializeComponent();

            ganiView = new GaniViewer();
            ganiView.Parent = GVPanel;
            ganiView.parentWindow = this;
            ganiView.SetBounds(0, 0, GVPanel.Width, GVPanel.Height);
            ganiView.init();

            dirBox.DropDownStyle = ComboBoxStyle.DropDownList;

            dirBox.SelectedIndex = 2;
        }

        public void tick()
        {
            ganiView.render();
        }

        private void gvWindow_dragDrop(object sender, DragEventArgs e)
        {
            MessageBox.Show(e.Data.ToString());
        }

        private void nextButton_Click(object sender, EventArgs e)
        {
            ganiView.currentGani.currentFrame++;
        }

        private void prevButton_Click(object sender, EventArgs e)
        {
            ganiView.currentGani.currentFrame--;
        }

        private void playButton_Click(object sender, EventArgs e)
        {
            ganiView.currentGani.playing = !ganiView.currentGani.playing;
            if (ganiView.currentGani.playing)
            {
                playButton.Text = "||";
            }
            else
            {
                playButton.Text = ">";
            }
        }

        private void helpButton_Click(object sender, EventArgs e)
        {
            MessageBox.Show("Drag and drop a gani to switch the animation.\n\nDrop a head, body, sword, shield, or hat on this window to set the image.\n\nOnce the image is dropped on for the first time, you can just hit save in your image editing program and it will automatically update over here as soon as you hit the save button.\n\nYou can scroll the gani around by clicking and dragging on it\n\n~Downsider", "Help!");
        }

        private void GVPanel_Paint(object sender, PaintEventArgs e)
        {

        }

        private void closing(object sender, FormClosingEventArgs e)
        {
            done = true;
        }
    }
}
