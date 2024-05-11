# KO YouTube Embed Plugin
An open source YouTube embedder

This is very much a work in progress. I created this to embed YouTube videos in WordPress posts using YouTube API. Specifically to be able to load shorts and playlists automatically with shortcodes.

I'm working on improving the performance and considering adding additional features.

If you like this plugin or want support please [leave a tip](https://kevinsguides.com/tips).

# Setup
A YouTube Data API v3 key is required for this to work. You will need to create a data api key at [Google Cloud Console](https://console.cloud.google.com). This is free (up to a limit you likely won't hit).

Install plugin (upload all files to wp-content/plugins/ko-yt-embed)

Paste API key into settings field under Settings -> KO YouTube Embed and hit save

You can now hopefully use shortcodes in articles (or text editor field of Elementor)

# Shortcodes

## A single video in landscape orientation
[ko-yt-single video="url"]
video = url of the video (you can get from share button on video page)
Example: [ko-yt-single video="https://www.youtube.com/watch?v=2341432_x"]

## The latest short from a YouTube short playlist
[ko-yt-latest-short playlist="url"]

## A grid of videos from a playlist in a two-column layout
[ko-yt-grid playlist="url" max="4"]

max = number of videos to load