jQuery(document).ready(function ($) {
	
		// Check if there is any map to initialize
		if ($('.breezy-map').length > 0) {
			$('.breezy-map').each(function(){
				
				var mapId = $(this).data('id');

				$.ajax({
					url: breezyMapFrontendAjax.ajaxurl,
					type: 'POST',
					data: {
						action: 'breezy_map_load_map_data',
						map_id: mapId,
						nonce: $('#breezy_map_load_map_data_nonce').val().trim()
					},
					success: function(response) {
						if(response.success) {

							// Add map type check here in the future
							
							var latitude = response.data.latitude;
							var longitude = response.data.longitude;
							var title = response.data.title;
							var zoom = response.data.zoom;

							// Initialize the map
							
							
							var currMap = $('.breezy-map[data-id="' + mapId + '"]')[0]
							
							var mapObj = L.map(currMap, {
								center: L.latLng(latitude, longitude),
								zoom: zoom
							});

							var basemap = L.tileLayer('https://www.onemap.gov.sg/maps/tiles/Default/{z}/{x}/{y}.png', {
								detectRetina: true,
								maxZoom: 19,
								minZoom: 11,
								attribution: '<img src="https://www.onemap.gov.sg/web-assets/images/logo/om_logo.png" style="height:20px;width:20px;"/>&nbsp;<a href="https://www.onemap.gov.sg/" target="_blank" rel="noopener noreferrer">OneMap</a>&nbsp;&copy;&nbsp;contributors&nbsp;&#124;&nbsp;<a href="https://www.sla.gov.sg/" target="_blank" rel="noopener noreferrer">Singapore Land Authority</a>'
							});

							basemap.addTo(mapObj);

							// Add the main map marker
							//L.marker([latitude, longitude]).addTo(mapObj)
							//	.bindPopup(title)
							//	.openPopup();

							// Loop through and add each marker associated with the map
							response.data.markers.forEach(function(marker) {
								
								
									var leftHtmlString = ""
									var oneColClass = ""
									
									if (marker.marker_image !== "") {
										leftHtmlString = `<div class="left-wrapper"><img src="${marker.marker_image}" alt="${marker.marker_address}" /></div>`
									} else {
										oneColClass = "one-col-wrapper"
									}
									
									var rightHtmlString = ""
									if (marker.marker_title !== "") {
										rightHtmlString += `<h4>${marker.marker_title}</h4>`
									}
									if (marker.marker_address !== "") {
										rightHtmlString += `<p>${marker.marker_address}</p>`
									}
									if (marker.marker_description !== "") {
										rightHtmlString += `<p>${marker.marker_description}</p>`
									}

									var popupContent = `
										<div class="breezy-map-marker-info-wrapper">
											<div class="flex-wrapper ${oneColClass}">
												${leftHtmlString}
												<div class="right-wrapper">${rightHtmlString}</div>
											</div>
										</div>
									`;
								
								
								L.marker([marker.marker_latitude, marker.marker_longitude]).addTo(mapObj)
									.bindPopup(popupContent)
									.openPopup();
							});
							
						} else {
							console.error('Failed to load the marker form.');
						}
					},
					error: function() {
						console.error('An error occurred while processing the request.');
					}
				});
			})
		}
		
});