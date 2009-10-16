var Sample = {};

$(document).ready(function(){

	var search_url = $("link[rel='search']").attr('href');

	var term = $("meta[name='searchTerm']").attr('content');

	$.getJSON(search_url,{q:term},function(json) {
		$.each(json.items,function(i,item) {
			if (item.metadata.title) {
				var title = item.metadata.title[0];
			} else {
				var title = 'no title';
			}
			Sample.placeItem(json.app_root+item.media.thumbnail,title);
		});
	});


});

Sample.placeItem = function(thumb_url,title) {
	var li = '<li><img src="'+thumb_url+'">'+title+'</li>';
	$('#favorites').append(li);
};
