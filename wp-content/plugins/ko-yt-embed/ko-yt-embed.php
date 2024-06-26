<?php

/**
 * Plugin Name: KO YouTube Embedder
 * Plugin URI: https://kevino.dev
 * Description: Shortcodes to embed YT playlist videos
 * Version: 0.05
 * Author: Kevin Olson
 * Author URI: https://kevino.dev
 * License: MIT
 */

 /*
 Shortcodes:
[ko-yt-single video="url"]
[ko-yt-latest-short playlist="url"]
[ko-yt-grid playlist="url" max="4"]
 */


/**
 * Activation
 */

function ko_yt_embed_activate()
{


}

register_activation_hook(__FILE__, 'ko_yt_embed_activate');


/**
 * Register admin menu
 */

function ko_yt_embed_register_admin_menu()
{
    add_options_page('KO YouTube Embedder', 'KO YouTube Embed', 'manage_options', 'ko-yt-embed-settings-group', 'ko_yt_embed_options_page');
}

add_action('admin_menu', 'ko_yt_embed_register_admin_menu');

function ko_yt_embed_options_page()
{
    //include settings.php
    include_once('settings.php');
}



/**
 * Register settings
 */

function ko_yt_embed_register_settings()
{
    add_option('ko_yt_api_key', 'YouTube API KEY');
    register_setting('ko-yt-embed-settings-group', 'ko_yt_api_key');
}

add_action('admin_init', 'ko_yt_embed_register_settings');


function add_script_style(){

    wp_enqueue_style('ko-yt-embed-style', plugins_url('ko-yt-embed.css', __FILE__));
    wp_enqueue_script('ko-yt-embed-script', plugins_url('thumbnailhandler.js', __FILE__), array(), false, [
        'defer' => 'defer'
    ]);
    wp_localize_script('ko-yt-embed-script', 'koYtEmbed', array(
        'baseUrl' => site_url(),
    ));
}

/**
 * Shows the latest youtube video from a reels/shorts playlist in portrait orientation
 */
function shortcode_latest_short_from_playlist($atts)
{

    // Get the latest video from a playlist
    $playlistUrl = $atts['playlist'];

    add_script_style();


    $output = '
    <div class="ko-yt-vid-container"
    data-latest-reel-playlist="' . $playlistUrl . '"
    data-orientation="portrait" title="Click to play">
        <img class="ko-yt-brandicon" src="' . plugins_url('youtube.svg', __FILE__) . '">
        <img class="ko-yt-img-thumbnail" src="' . plugins_url('youtube-bg-loading.svg', __FILE__) . '">
    </div>';
    return $output;
}

add_shortcode('ko-yt-latest-short', 'shortcode_latest_short_from_playlist');



/**
 * Displays a grid of videos from a playlist in landscape orientation with a title above each video
 */
function shortcode_playlist_grid($atts)
{

    // Define default values for your shortcode attributes
    $atts = shortcode_atts(
        array(
            'max' => 4, // default number of videos to display
            'playlist' => '', // default playlist URL
            'columns' => '2', // default number of columns
            'theme' => '',
            'gap' => '',
        ),
        $atts
    );

    //is playlist a URL or ID?
    $playlistUrl = $atts['playlist'];
    if (strpos($playlistUrl, 'list=') !== false) {
        $playlistId = substr($playlistUrl, strpos($playlistUrl, 'list=') + 5);
    } else {
        $playlistId = $playlistUrl;
    }

    //check if $gap is numeric
    if (!is_numeric($atts['gap'])) {
        $atts['gap'] = '10px';
    }
    else{
        $atts['gap'] = $atts['gap'] . 'px';
    }

    $maxResults = $atts['max']; // number of videos to display

    $videos = get_videos_from_playlist($playlistId, $maxResults);

    add_script_style();


    return render_grid($videos, $atts);
}
add_shortcode('ko-yt-grid', 'shortcode_playlist_grid');



/**
 * Displays a grid of latest videos from a channel in landscape orientation with a title above each video
 */
function shortcode_latest_from_channel_grid($atts){
    $atts = shortcode_atts(
        array(
            'max' => 4, // default number of videos to display
            'channel' => '', // channel id
            'columns' => '2', // default number of columns
            'theme' => '',
            'gap' => '',
        ),
        $atts
    );

    $channelID = $atts['channel'];

    //check if $gap is numeric
    if (!is_numeric($atts['gap'])) {
        $atts['gap'] = '10px';
    }
    else{
        $atts['gap'] = $atts['gap'] . 'px';
    }

    $maxResults = $atts['max']; // number of videos to display

    $videos = get_latest_videos_from_channel($channelID, $maxResults);

    add_script_style();

    return render_grid($videos, $atts);

}
add_shortcode('ko-yt-channel-grid', 'shortcode_latest_from_channel_grid');

function get_latest_videos_from_channel($channelID, $maxResults = 4){
    
        $apiKey = get_option('ko_yt_api_key');
        $channelUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=' . $channelID . '&order=date&maxResults=' . $maxResults . '&key=' . $apiKey;
        $data = json_decode(file_get_contents($channelUrl));
        $videos = [];
        foreach($data->items as $item){
            if($item->id->kind == 'youtube#video'){
                $video = [
                    'id' => $item->id->videoId,
                    'title' => $item->snippet->title,
                    'thumb' => $item->snippet->thumbnails->medium->url,
                    'description' => $item->snippet->description,
                ];
                $videos[] = $video;
            }
        }
        return $videos;

}


function render_grid($videos, $atts){

    // print all videos in playlist
    $output = '<div class="koyt-grid-wrapper" style="--koytcolumns: ' . $atts['columns'] . '; --koytgap: ' . $atts['gap'] . ';">';
    foreach($videos as $video){
        $output .= '
        <div class="koyt-videobox ' . $atts['theme'] . '">
            <span class="koyt-videotitle">' . $video['title'] . '</span>
            <div class="ko-yt-vid-container" data-orientation="landscape" data-video-id="' . $video['id'] . '" title="Click to play">
                <img class="ko-yt-brandicon" src="' . plugins_url('youtube.svg', __FILE__) . '">
                <img class="ko-yt-img-thumbnail" src="'.$video['thumb'].'">
            </div>
        </div>';
    }
    $output .= '</div>';

    return $output;
}

/**
 * Gets a playlist from the YouTube API
 * @param string $playlistUrl The URL of the playlist
 * @return object The playlist object (decoded JSON)
 */
function get_videos_from_playlist($playlistId, $maxResults = 1){

    //check if playlistID is a URL or ID
    if (strpos($playlistId, 'list=') !== false) {
        $playlistId = substr($playlistId, strpos($playlistId, 'list=') + 5);
    }

    //Try to get playlist from transient
    $playlist = get_transient('ko_yt_embed_playlist_' . $playlistId . $maxResults);

    //If not found, get playlist from API and set transient
    if (!$playlist) {
        $apiKey = get_option('ko_yt_api_key');

        try{
            $playlistUrl = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults='.$maxResults.'&playlistId=' . $playlistId . '&key=' . $apiKey;
            $playlist = json_decode(file_get_contents($playlistUrl));
            set_transient('ko_yt_embed_playlist_' . $playlistId . $maxResults, $playlist, 60 * 60 * 1);
            //set backup transient for 48 hours
            set_transient('ko_yt_embed_playlist_backup_' . $playlistId . $maxResults, $playlist, 60 * 60 * 48);
        }
        catch(Exception $e){
            //If API call fails, try to get from backup transient
            $playlist = get_transient('ko_yt_embed_playlist_backup_' . $playlistId. $maxResults);

        }

    }


    $videos = [];
    foreach($playlist->items as $item){
        if($item->kind == 'youtube#playlistItem'){
            $video = [
                'id' => $item->snippet->resourceId->videoId,
                'title' => $item->snippet->title,
                'thumb' => $item->snippet->thumbnails->maxres->url,
                'description' => $item->snippet->description,
            ];
            $videos[] = $video;
        }
    }

    return $videos;

}


function get_video($videoId){
    
        //Try to get video from transient
        $video = get_transient('ko_yt_embed_video_' . $videoId);
    
        //If not found, get video from API and set transient
        if (!$video) {
            $apiKey = get_option('ko_yt_api_key');
    
            try{
                $videoUrl = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $videoId . '&key=' . $apiKey;
                $video = json_decode(file_get_contents($videoUrl));
                set_transient('ko_yt_embed_video_' . $videoId, $video, 60 * 60 * 12);
                //set backup transient for 48 hours
                set_transient('ko_yt_embed_video_backup_' . $videoId, $video, 60 * 60 * 48);
            }
            catch(Exception $e){
                //If API call fails, try to get from backup transient
                $video = get_transient('ko_yt_embed_video_backup_' . $videoId);
    
            }
    
        }
    
        return $video;
    
    
}


/**
 * Displays a single video in landscape orientation
 */
function shortcode_single_video($atts){
    // Define default values for your shortcode attributes
    $atts = shortcode_atts(
        array(
            'video' => '', // default video URL
        ),
        $atts
    );

    $videoUrl = $atts['video'];
    $videoId = substr($videoUrl, strpos($videoUrl, 'v=') + 2);

    add_script_style();

    // print all videos in playlist
    $output = '
    <div class="ko-yt-vid-container"
    data-video-id="' . $videoId . '"
    data-orientation="landscape" title="Click to play">
        <img class="ko-yt-brandicon" src="' . plugins_url('youtube.svg', __FILE__) . '">
        <img class="ko-yt-img-thumbnail" src="' . plugins_url('youtube-bg-loading.svg', __FILE__) . '">
    </div>';
    return $output;


}
add_shortcode('ko-yt-single', 'shortcode_single_video');

/**
 * Displays a single video in reel/short portrait orientation
 */
function shortcode_single_video_short($atts){

    $atts = shortcode_atts(
        array(
            'video' => '', // default video URL
        ),
        $atts
    );

    $videoUrl = $atts['video'];
    //check if url or id
    if (strpos($videoUrl, 'shorts/') !== false) {
        $videoId = substr($videoUrl, strpos($videoUrl, 'shorts/') + 7, 11);
    } else {
        $videoId = $videoUrl;
    }

    add_script_style();

    // print all videos in playlist
    $output = '
    <div class="ko-yt-vid-container"
    data-video-id="' . $videoId . '"
    data-orientation="portrait" title="Click to play">
        <img class="ko-yt-brandicon" src="' . plugins_url('youtube.svg', __FILE__) . '">
        <img class="ko-yt-img-thumbnail" src="' . plugins_url('youtube-bg-loading.svg', __FILE__) . '">
    </div>';
    return $output;

}
add_shortcode('ko-yt-single-short', 'shortcode_single_video_short');

// REST

function fetch_video_data($request)
{
    
    $videoId = $request['vid'];
    $video = get_video($videoId);
    return $video;

}

function fetch_video_thumbnail($request){
    
    $videoId = $request['vid'];
    $video = get_video($videoId);
    $thumbnailUrl = $video->items[0]->snippet->thumbnails->maxres->url;
    // return url
    return $thumbnailUrl;
}


function fetch_latest_in_playlist($request)
{

    $playlistId = $request['pid'];
    $playlist = get_videos_from_playlist($playlistId, 1);

    return $playlist[0];

}


function register_ko_yt_rest_route()
{

    //register rest route with format ko-yt-embed/v1/ytvid/VIDEO_ID
    register_rest_route('ko-yt-embed/v1', '/video/(?P<vid>[a-zA-Z0-9_-]+)', array(
        'methods' => 'GET',
        'callback' => 'fetch_video_data',
        'args' => array(
            'vid' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return preg_match('/^[a-zA-Z0-9_-]+$/', $param);
                }
            )
        ),
    ));

    register_rest_route('ko-yt-embed/v1', '/video-thumb/(?P<vid>[a-zA-Z0-9_-]+)', array(
        'methods' => 'GET',
        'callback' => 'fetch_video_thumbnail',
        'args' => array(
            'vid' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return preg_match('/^[a-zA-Z0-9_-]+$/', $param);
                }
            )
        ),
    ));

    register_rest_route('ko-yt-embed/v1', '/latest-in-playlist', array(
        'methods' => 'POST',
        'callback' => 'fetch_latest_in_playlist',
        'args' => array(
            'pid' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return preg_match('/^[a-zA-Z0-9_-]+$/', $param);
                }
            )
        ),

    ));


}

add_action('rest_api_init', 'register_ko_yt_rest_route');

