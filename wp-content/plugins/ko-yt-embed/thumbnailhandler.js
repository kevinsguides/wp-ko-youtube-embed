document.addEventListener('DOMContentLoaded', function() {


//when someone clicks a youtube thumbnail, load the video in its place
const baseUrl = koYtEmbed.baseUrl;

document.querySelectorAll('.ko-yt-embed-thumbnail').forEach(item => {
    console.log('found a thumbnail');
    let videoId = item.getAttribute('data-video-id');
    let aspectRatio = item.getAttribute('data-aspect-ratio');
    item.addEventListener('click', event => { 
        if (aspectRatio == 'portrait'){
            item.innerHTML = '<div class="koyt-reel-wrapper"><iframe width="500" height="782" src="https://www.youtube.com/embed/' + videoId + '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        }
        else{

            item.innerHTML = '<div class="koyt-landscape-embed-wrapper"><iframe width="1080" height="720" src="https://www.youtube.com/embed/' + videoId + '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        }
    }
    )
});


document.querySelectorAll('.ko-yt-vid-container').forEach(item => {

    // render based off id
    let videoId = item.getAttribute('data-video-id');
    console.log('video id: ' + videoId);
    if (videoId != null){
        renderVideo(item, videoId);
    }

    let latestShortFromPlaylistId = item.getAttribute('data-latest-reel-playlist');
    if (latestShortFromPlaylistId != null){
        let fetchLink = baseUrl + '/wp-json/ko-yt-embed/v1/latest-in-playlist/' + latestShortFromPlaylistId;
        async function fetchLatestInPlaylist() {
            let response = await fetch(fetchLink);
            //the response is the video information json
            let data = await response.json();

            let videoId = data.snippet.resourceId.videoId;
            let thumbUrl = data.snippet.thumbnails.maxres.url;
            renderVideo(item, videoId, thumbUrl);
        }
        fetchLatestInPlaylist();
        
    }

});

/**
 * 
 * @param {*} item - container element (.ko-yt-vid-container)   must have data-aspect-ratio set
 * @param {*} videoId - youtube video id to embed
 * @param {*} thumbUrl - optional, url to thumbnail image if we already have it
 */
function renderVideo(item, videoId, thumbUrl = null){

    console.log('baseurl is ' + baseUrl);


    let orientation = item.getAttribute('data-orientation');

    if (thumbUrl == null){
        let fetchLink = baseUrl + '/wp-json/ko-yt-embed/v1/video-thumb/' + videoId;

        async function fetchThumbnail() {
            let data;
            try {
                let response = await fetch(fetchLink);
                let data = await response.json();
                console.log('fetched thumbnail: ' + data);
                item.querySelector('.ko-yt-img-thumbnail').src = data;
            } catch (error) {
                console.error('KOYT Error:', error);
                console.log('data: ' + data);
            }
        }
        
        fetchThumbnail();



    }
    else{
        item.querySelector('.ko-yt-img-thumbnail').src = thumbUrl;
    }

    console.log('attempting to render video with id: ' + videoId);
    

    item.addEventListener('click', event => { 
        if (orientation == 'portrait'){
            item.innerHTML = '<div class="koyt-reel-wrapper"><iframe width="500" height="782" src="https://www.youtube.com/embed/' + videoId + '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        }
        else{
            item.innerHTML = '<div class="koyt-landscape-embed-wrapper"><iframe width="1080" height="720" src="https://www.youtube.com/embed/' + videoId + '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        }
    }
    );



}

});