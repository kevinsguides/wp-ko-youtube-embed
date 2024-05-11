<?php

/**
 * Plugin Name: KO YT Embed
 * Plugin URI: https://kevino.dev
 * Description: Shortcodes to embed YT playlist videos
 * Version: 1.55
 * Author: Kevin Olson
 * Author URI: https://kevino.dev
 * License: Proprietary
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
    add_options_page('Ko YT Embed', 'Ko YT Embed', 'manage_options', 'ko-yt-embed-settings-group', 'ko_yt_embed_options_page');
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



/**
 * Shows the latest youtube video from a reels/shorts playlist in portrait orientation
 */
function shortcode_latest_short_from_playlist($atts)
{

    // Get the latest video from a playlist
    $playlistUrl = $atts['playlist'];

    // add stylesheet
    wp_enqueue_style('ko-yt-embed-style', plugins_url('ko-yt-embed.css', __FILE__));

    //add js with defer
    wp_enqueue_script('ko-yt-embed-script', plugins_url('thumbnailhandler.js', __FILE__), array(), false, [
        'defer' => 'defer'
    ]);

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
        ),
        $atts
    );


    $playlistUrl = $atts['playlist'];

    $maxResults = $atts['max']; // number of videos to display

    $playlist = get_playlist($playlistUrl, $maxResults);

    // add stylesheet
    wp_enqueue_style('ko-yt-embed-style', plugins_url('ko-yt-embed.css', __FILE__));

    //add js with defer
    wp_enqueue_script('ko-yt-embed-script', plugins_url('thumbnailhandler.js', __FILE__), array(), false, [
        'defer' => 'defer'
    ]);

    // print all videos in playlist
    $output = '<div class="koyt-grid-wrapper">';
    foreach($playlist->items as $video){
        $output .= '
        <div class="koyt-grid-item">
            <span class="koyt-grid-video-title">' . $video->snippet->title . '</span>
            <div class="ko-yt-embed-thumbnail" data-video-id="' . $video->snippet->resourceId->videoId . '" data-aspect-ratio="landscape" title="Click to play">
                <img class="ko-yt-brandicon" src="' . plugins_url('youtube.svg', __FILE__) . '">
                <img class="ko-yt-img-landscape" src="' . $video->snippet->thumbnails->maxres->url . '">
            </div>
        </div>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('ko-yt-grid', 'shortcode_playlist_grid');



/**
 * Gets a playlist from the YouTube API
 * @param string $playlistUrl The URL of the playlist
 * @return object The playlist object (decoded JSON)
 */
function get_playlist($playlistId, $maxResults = 1){


    //Try to get playlist from transient
    $playlist = get_transient('ko_yt_embed_playlist_' . $playlistId);

    //If not found, get playlist from API and set transient
    if (!$playlist) {
        $apiKey = get_option('ko_yt_api_key');

        try{
            $playlistUrl = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults='.$maxResults.'&playlistId=' . $playlistId . '&key=' . $apiKey;
            $playlist = json_decode(file_get_contents($playlistUrl));
            set_transient('ko_yt_embed_playlist_' . $playlistId, $playlist, 60 * 60 * 1);
            //set backup transient for 48 hours
            set_transient('ko_yt_embed_playlist_backup_' . $playlistId, $playlist, 60 * 60 * 48);
        }
        catch(Exception $e){
            //If API call fails, try to get from backup transient
            $playlist = get_transient('ko_yt_embed_playlist_backup_' . $playlistId);

        }

    }

    return $playlist;

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
                set_transient('ko_yt_embed_video_' . $videoId, $video, 60 * 60 * 1);
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

    // add stylesheet
    wp_enqueue_style('ko-yt-embed-style', plugins_url('ko-yt-embed.css', __FILE__));

    //add js
    wp_enqueue_script('ko-yt-embed-script', plugins_url('thumbnailhandler.js', __FILE__), array(), false, [
        'defer' => 'defer'
    ]);

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
    return $video->items[0]->snippet->thumbnails->maxres->url;

}

function fetch_latest_in_playlist($request)
{

    $playlistId = $request['pid'];
    $playlist = get_playlist($playlistId);

    $latestVideo = $playlist->items[0];

    return $latestVideo;


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

    register_rest_route('/ko-yt-embed/v1', 'video-thumb/(?P<vid>[a-zA-Z0-9_-]+)', array(
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

    register_rest_route('ko-yt-embed/v1', '/latest-in-playlist/(?P<pid>[a-zA-Z0-9_-]+)', array(
        'methods' => 'GET',
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

