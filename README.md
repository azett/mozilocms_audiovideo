# moziloCMS plugin: Audio/video player
Simple audio and video player plugin for [moziloCMS](https://github.com/moziloDasEinsteigerCMS/) 1.12 and 2.0.

## Supported file types:
- Audio: mp3, ogg, wav
- Video: mp4, webm, ogg

## Usage
File from current category:

    {audiovideo|file.mp4}


File from different category:

    {audiovideo|Category:file.mp4}


External file:

    {audiovideo|http://www.example.org/file.mp4}

## Plugin parameters
You may set parameters for each player separately. This will override the global plugin settings.

    {audiovideo|file.mp4|controls=1,autoplay=1,width=600,height=480}

List of parameters:
- `controls`: Show player controls. Values: 0 (no) / 1 (yes)
- `autoplay`: Start playing immediately. Values: 0 (no) / 1 (yes)
- `width`: Width of the player (video only)
- `height`: Height of the player (video only)

## Technical details
- According to the given file type, a HTML5 multimedia element is created: `<audio>` for audio files, `<video>` for video files.
- If neither the plugin configuration nor the player instance have set size values, videos will be displayed in their actual dimensions.
- The audio/video elements created by the plugin may be styled with their CSS class `audiovideo` (see plugin.css).
