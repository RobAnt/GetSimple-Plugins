<!-- Based on tumblrBadge by Robert Nyman, http://www.robertnyman.com/, http://code.google.com/p/tumblrbadge/ --><script type="text/javascript">
var simpletumblr = function () {

	// fail gracefully, ie: do nothing if used incorrectly
	var userName = window.simpletumblr_name;
	var itemsToShow = parseInt(window.simpletumblr_count);
	var itemToAddTo = window.simpletumblr_divname;
	var imageSize = parseInt(window.simpletumblr_imagesize);
	var shortPublishDate = window.simpletumblr_shortdate;
	var timeToWait = parseInt(window.simpletumblr_waittime);
	var postType = window.simpletumblr_type;
	var startNum = parseInt(window.simpletumblr_start);
	
	//set defaults input
	if ( !userName ) {
		userName = "cartocopia";
	}
	if ( !itemsToShow ) {
		itemsToShow = 10;
	}
	if ( !itemToAddTo ) {
		itemToAddTo = "simpletumblr-div";
	}
	if ( !imageSize ) {
		imageSize = 500;
	}
	if ( !shortPublishDate ) {
		shortPublishDate = false;
	}
	if ( !timeToWait ) {
		timeToWait = 2000;
	}
	if ( !postType ) {
		postType = "";
	}
	if ( !startNum ) {
		startNum = 0;
	}
		
	// User settings
	var settings = {
		userName : userName ,       // Your Tumblr user name
		itemsToShow : itemsToShow , // Number of Tumblr posts to retrieve
		itemToAddTo : itemToAddTo , // Id of HTML element to put tumblr code into
		imageSize : imageSize ,     // Values can be 75, 100, 250, 400 or 500
		shortPublishDate : shortPublishDate , // Whether the publishing date should be cut shorter
		timeToWait : timeToWait,    // Milliseconds, 1000 = 1 second
		postType : postType,        // blank or text, quote, photo, link, video, or audio
		startNum : startNum         // first post to display
	};
	
	// html code functionality
	var head = document.getElementsByTagName("head")[0];
	var tumblrContainer = document.getElementById(settings.itemToAddTo);
	if (head && tumblrContainer) {
		var tumblrJSON = document.createElement("script");
		tumblrJSON.type = "text/javascript";
		tumblrJSON.src = "http://" + settings.userName + ".tumblr.com/api/read/json?callback=simpletumblr.listItems&num=" + settings.itemsToShow + "&start=" + settings.startNum;
		if ( settings.postType != "" ) {
			tumblrJSON.src = tumblrJSON.src + "&type=" + settings.postType;
		}
		head.appendChild(tumblrJSON);
		
		var wait = setTimeout(function () {
			tumblrJSON.onload = null;
			tumblrJSON.parentNode.removeChild(tumblrJSON);
			tumblrJSON = null;
		}, settings.timeToWait);
		
		listItems = function (json) {
			var posts = json.posts,
				list = document.createElement("ol"), 
				post, 
				listItem, 
				text, 
				link, 
				img, 
				conversation, 
				postLink;
				
			var total = parseInt(json['posts-total']);
			
			list.className = "tumblr_posts";
			for (var i=0, il=posts.length; i<il; i=i+1) {
				post = posts[i];

				// Only get content for text, photo, quote, link and video posts
				if (/regular|photo|quote|link|video/.test(post.type)) {
					listItem = document.createElement("li");
					text = post["regular-body"] || post["photo-caption"] || post["quote-source"] || post["link-text"] || post["link-url"] || post["video-caption"] || "";
					if (post.type === "photo") {
						listItem.className = "tumblr_post tumblr_photo_post";
											
						img = document.createElement("img");
						// To avoid a creeping page
						img.width = settings.imageSize;
						img.src = post["photo-url-" + settings.imageSize];
						img.className = "tumblr_photo";
						listItem.appendChild(img);
						
						caption = document.createElement("div");
						caption.className = "tumblr_caption";
						caption.innerHTML = post["photo-caption"];
						listItem.appendChild(caption);
					}
					else if (post.type === "regular") {
						listItem.className = "tumblr_post tumblr_text_post";
						
						if ( post["regular-title"] !== null ) {
							title = document.createElement("div");
							title.className = "tumblr_title";
							title.innerHTML = post["regular-title"];
							listItem.appendChild(title);
						}
						body = document.createElement("div");
						body.className = "tumblr_body";
						body.innerHTML = post["regular-body"];
						listItem.appendChild(body);
					}
					else if (post.type === "quote") {
						listItem.className = "tumblr_post tumblr_quote_post";
						
						quote = document.createElement("div");
						quote.className = "tumblr_quote";
						quote.innerHTML = '<span class="tumblr_open_quote">“</span>' + post["quote-text"] + '<span class="tumblr_close_quote">”</span>';
						listItem.appendChild(quote);
						
						source = document.createElement("div");
						source.className = "tumblr_source";
						source.innerHTML = post["quote-source"];
						listItem.appendChild(source);	
					}
					else if (post.type === "link") {
						listItem.className = "tumblr_post tumblr_link_post";
						
						link = document.createElement("a");
						link.href = post["link-url"];
						link.className = "tumblr_link";
						link.innerHTML = post["link-text"];
						listItem.appendChild(link);
						
						desc = document.createElement("div");
						desc.className = "tumblr_description";
						desc.innerHTML = post["link-description"];
						listItem.appendChild(desc);						
					}
					else if (post.type === "video") {
						listItem.className = "tumblr_post tumblr_video_post";

						video = document.createElement("div");
						video.className = "tumblr_video";
						video.innerHTML = post["video-player"];
						listItem.appendChild(video);
										
						caption = document.createElement("div");
						caption.className = "tumblr_caption";
						caption.innerHTML = post["video-caption"];
						listItem.appendChild(caption);
					}					

					// Create a link to Tumblr post
					postDate = document.createElement("div");
					postDate.className = "tumblr_post_date";
									
					postLink = document.createElement("a");
					postLink.href = post.url;
					postLink.innerHTML = (settings.shortPublishDate)? post["date"].replace(/(^\w{3},\s)|(:\d{2}$)/g, "") : post["date"];
					postDate.appendChild(postLink);
					
					listItem.appendChild(postDate);

					// Apply list item to list
					list.appendChild(listItem);
				}
			}
			
			// Apply list to container element
			tumblrContainer.innerHTML = "";
			tumblrContainer.appendChild(list);
			
			navDiv = document.createElement("div");
			navDiv.className  = "tumblr_nav";
			
			// older posts			
			if (total > settings.startNum + settings.itemsToShow) {
				olderDiv = document.createElement("div");
				olderDiv.style.cssFloat = "left";
				olderDiv.style.styleFloat = "left"; //for ie
				olderLink = document.createElement("a");
				olderLink.href = window.location.href.split('?')[0] + "?start=" + (settings.startNum + settings.itemsToShow);
				olderLink.innerHTML = "< Older";
				
				olderDiv.appendChild(olderLink);
				navDiv.appendChild(olderDiv);
			}
			
			//newer posts
			if (settings.startNum > 0) {
				if (settings.startNum > settings.itemsToShow) {
					newerDiv = document.createElement("div");
					newerDiv.style.cssFloat = "right";
					newerDiv.style.styleFloat = "right"; //for ie
					newerLink = document.createElement("a");
					newerLink.href = window.location.href.split('?')[0] + "?start=" + (settings.startNum - settings.itemsToShow);
					newerLink.innerHTML = "Newer >";
				
					newerDiv.appendChild(newerLink);
					navDiv.appendChild(newerDiv);
				} 
				else if (settings.startNum = settings.itemsToShow) {
					newerDiv = document.createElement("div");
					newerDiv.style.cssFloat = "right";
					newerDiv.style.styleFloat = "right"; //for ie
					newerLink = document.createElement("a");
					newerLink.href = window.location.href.split('?')[0];
					newerLink.innerHTML = "Newer >";
				
					newerDiv.appendChild(newerLink);
					navDiv.appendChild(newerDiv);
				}
			}
			
			tumblrContainer.appendChild(navDiv);
				
		};
		
		return {
			listItems : listItems
		};
	}
}();