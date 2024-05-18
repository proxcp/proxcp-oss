$(document).ready(function() {
	// Session map
	$('#geoipmap').vectorMap({
	    map: 'world_mill',
	    scaleColors: ['#C8EEFF', '#0071A4'],
	    normalizeFunction: 'polynomial',
	    hoverOpacity: 0.7,
	    hoverColor: false,
	    markerStyle: {
	        initial: {
	            fill: '#F8E23B',
	            stroke: '#383f47'
	        }
	    },
	    backgroundColor: '#383f47',
			zoomButtons: false,
			onRegionTipShow: function(e, label, code) {
				e.preventDefault();
			}
	});
	var map = $('#geoipmap').vectorMap('get', 'mapObject');
	$('.geoipdata').each(function(i, obj) {
	    var str = $(this).val();
	    var arr = str.split("#");
	    var coords = arr[3].split(" ");
	    var obj = {
	        latLng: [parseFloat(coords[0]), parseFloat(coords[1])]
	    };
	    map.addMarker(arr[1], obj);
	});
});
