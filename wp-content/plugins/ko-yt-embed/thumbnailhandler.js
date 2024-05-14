document.addEventListener("DOMContentLoaded", function () {
  //when someone clicks a youtube thumbnail, load the video in its place
  const baseUrl = koYtEmbed.baseUrl;

  document.querySelectorAll(".ko-yt-embed-thumbnail").forEach((item) => {

    let videoId = item.getAttribute("data-video-id");
    let aspectRatio = item.getAttribute("data-aspect-ratio");
    item.addEventListener("click", (event) => {
      if (aspectRatio == "portrait") {
        item.innerHTML =
          '<div class="koyt-reel-wrapper"><iframe width="500" height="782" src="https://www.youtube.com/embed/' +
          videoId +
          '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
      } else {
        item.innerHTML =
          '<div class="koyt-landscape-embed-wrapper"><iframe width="1080" height="720" src="https://www.youtube.com/embed/' +
          videoId +
          '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
      }
    });
  });

  document.querySelectorAll(".ko-yt-vid-container").forEach((item) => {
    // render based off id
    let videoId = item.getAttribute("data-video-id");

    if (videoId != null) {
      renderVideo(item, videoId);
    }

    let latestShortFromPlaylistId = item.getAttribute(
      "data-latest-reel-playlist"
    );

    if (latestShortFromPlaylistId != null) {
      let data = {
        pid: latestShortFromPlaylistId,
      };
      fetch(
        baseUrl + "/wp-json/ko-yt-embed/v1/latest-in-playlist",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(data),
        }
      )
        .then((response) => response.json())
        .then((data) => {
            console.dir(data);
            let videoId = data.id;
            let thumbUrl = data.thumb;
            renderVideo(item, videoId, thumbUrl);
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    }
  });


  /**
   *
   * @param {*} item - container element (.ko-yt-vid-container)   must have data-aspect-ratio set
   * @param {*} videoId - youtube video id to embed
   * @param {*} thumbUrl - optional, url to thumbnail image if we already have it
   */
  function renderVideo(item, videoId, thumbUrl = null) {

    let orientation = item.getAttribute("data-orientation");

    if (thumbUrl == null) {
      let fetchLink =
        baseUrl + "/wp-json/ko-yt-embed/v1/video-thumb/" + videoId;

      async function fetchThumbnail() {
        let data;
        try {
          let response = await fetch(fetchLink);
          let data = await response.json();
          item.querySelector(".ko-yt-img-thumbnail").src = data;
        } catch (error) {
          console.error("KOYT Error:", error);
        }
      }

      fetchThumbnail();
    } else {
      item.querySelector(".ko-yt-img-thumbnail").src = thumbUrl;
    }

    item.addEventListener("click", (event) => {
      if (orientation == "portrait") {
        item.innerHTML =
          '<div class="koyt-reel-wrapper"><iframe width="500" height="782" src="https://www.youtube.com/embed/' +
          videoId +
          '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
      } else {
        item.innerHTML =
          '<div class="koyt-landscape-embed-wrapper"><iframe width="1080" height="720" src="https://www.youtube.com/embed/' +
          videoId +
          '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
      }
    });
  }
});
