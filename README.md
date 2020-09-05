# timelapse
**tl;dr**: Create a timelapse video from a bunch of input files using ffmpeg

To my surprise, there seems to be no free tool to combine a bunch of images to a video. Of course, there is
 ffmpeg, but I wanted  bit more - multiple scenes, zooming in to the focus area, cropping the source images, etc.

So, I created my own, using Imagick and ffmpeg.

**Caveat**: This is  command line tool using a config from a yaml file. If you want a UI version, feel free to use my
 code and create one.

## Installation
* ffmpeg needs to be installed. If your distribution does not include it, you can get it here: https://ffmpeg.org/download.html
* Requires PHP 7.4+ and the ext-imagick extension

If these prerequisites are satisfied, clone the repository and install the vendor libs:

```shell script
git clone https://github.com/CiderAndWhisky/timelapse.git
cd timelapse
composer install
```

## Usage
* Have a great idea for a timelapse
* Shoot some images with the camera of your choice
* Put them into a folder somewhere on your disk. Depending on the camera, the will be named DSCN1234.JPG or IMG001234
.jpg or some other pattern. The only important thing is that they are numbered somehow.
* Create a config file for your timelapse and name it MyAwesomeName.yaml:
```yaml
# If we have several scenes, we do not want to repeat the base folder name
srcRootPath: "/home/user/Pictures/MyAwesomeTimelapse"
output:
  # Temp files and the finished video will be generated here. This folder must not exist yet!
  path: "/home/user/Videos/Timelapse/SoAwesome"
  # The resolution of the generated video.
  # Make sure that the ratio fits the input images or the result will look stretched!
  resolution:
    width: 1600
    height: 1200
  # The framerate of the output video.
  # I recommend at least 20fps, or the video will flicker.
  # Good values are between 30 and 100
  fps: 30
scenes:
  # If you have more than one scene, just repeat this part and give them descriptive names
  - name: "Scene 1"
    # The duration of the scene in seconds
    duration: 20
    # The name of the images in the src folder.
    # The pattern {7} will result in the mage number to be something like DSCN0010183.JPG
    # If your camera uses IMG12345.jpg, use IMG{5}.jpg as image name.
    imageName: DSCN{7}.JPG
    # The start image of the scene
    start: 9591
    # The last image of the scene
    end: 13781
```
Now we are able to geneate the video using:
```shell script
bin/timelapse timelapse:generate /path/to/MyAwesomeName.yaml
```
If everything works as intended, you will see a progress bar, then some ffmpeg output, and finally this:
```shell script

 [OK] Created timelapse video: /home/user/Videos/Timelapse/SoAwesome/output.mp4

```
## Important notes
* Make sure you have at least `fps * duration` individual images. So, for a 20-second timelapse with 30 frames per
 second, you need at least 600 images.
* If you have more or less than the required number of images, `timelapse` will create intermediate images by
 blending images. This reduces flickering if source and target frame rate do not match, but it can **not** create
  content you did not photograph!
* For testing your timelapse, reduce resolution and fps to get a quick preview of the video. If you think you have it
 right, set the desired resolution and frame rate. Then: Grab a coffee... processing 5000 high resolution images is
  not a real-time experience...

 ## Adding more features
 ### Zooming
If you want to zoom into the picture, create a new scene and add the target zoom state.
```yaml
scenes:
  - name: "Preparation"
    duration: 3
    imageName: G{7}.JPG
    start: 19548
    end: 19552
  - name: "Evaporate!"
    duration: 5
    imageName: G{7}.JPG
    start: 19553
    end: 19829
  - name: "Add more water"
    duration: 2
    imageName: G{7}.JPG
    start: 19830
    end: 19853
  - name: "Evaporate again"
    duration: 5
    imageName: G{7}.JPG
    start: 19854
    end: 20330
  - name: "Grand finale"
    duration: 8
    imageName: G{7}.JPG
    start: 20330
    end: 20366
    zoomTo:
      top: 47
      left: 18
      size: 30
```
In this timelapse, I have splashed some water on a table. I have just 5 images of the splashing, but I want this to
 be an introduction, so I extend this to 3 seconds anyways, and the images will be blended over.

Then, we watch the sun using its powerful photons to turn the water into gas. Here, we show 270ish photos in 5
 seconds, so we speed up a little.

In between, I was bored and added some more water, this is shown in less speed. Then, more evaporation.

In the end, we zoom in at slower speed as the last puddle of water evaporates.

The zoom is given as percentages, so the zoom will go to the area of 47 to 77% of the image and 18 to 48% in the
 vertical.

Do not zoom in too much unless you have really high resolution and high quality input!
### Zoom start / Panning
Besides zooming in, you can also zoom from something, or combine zooming and panning to create a Ken-Burns-effect
. Use the zoomFrom directive (optionally in combination with the zomTo directive) to crop the timelapse, pan, zoom, etc.

## Options
* `--force` - If you run the command repeatedly it will fail the second time because the target folder already
 exists. Use the force to delete it before it creates it again. CHECK YOUR CONFIG before you do that, if target
  folder is  set to '/', you might have a surprise otherwise...
* `--keepTempImages` - By default, the temporary images rendered will be deleted after the video is created. Use this
 switch to keep them.
* `--cores` - If set to a value greater than 1, parallel rendering will be used. This will cause a significant
 speedup in rendering time. **WARNING**: Do not set to a higher value then the number of cores you have, rather use
  something like 80% of your cores. Otherwise, the system might become unresponsive during rendering time!

### A note on parallelisation
For my [example video](https://www.youtube.com/watch?v=NqrSRqagNSY), the rendering time was reduced from 19 minutes
 to less than 5 minutes using 10 of my 12 cores.
It does not scale linearly, because it also produces I/O which cannot be used in parallel. So, experiment with the
core count and the speedup, it depends highly on your hardware.
