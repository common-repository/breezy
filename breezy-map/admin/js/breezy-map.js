jQuery(document).ready(function ($) {
	
	// Tab 
	$('.tab-content .tab').first().addClass('active');
    
    $('.tab-links a').on('click', function(e) {
        e.preventDefault();
        
        $('.tab-links li').removeClass('active');
        $('.tab').removeClass('active');
        
        $(this).parent().addClass('active');

        var target = $(this).attr('href');

        $(target).addClass('active');
    });

	// Icons initialization
    let map, markerGroup;
    var iconBino = L.icon({
        iconUrl: '/wp-content/plugins/breezy/breezy-map/admin/images/binoculars-icon.svg',
        iconSize: [38, 24],
        iconAnchor: [19, 12],
        popupAnchor: [0, -24]
    });

	// Map initialization function
    function initDefaultMap(zoom) {

		//Add code to detect map type in the future, then decide what API to use
		
        let sw = L.latLng(1.144, 103.535);
        let ne = L.latLng(1.494, 104.502);
        let bounds = L.latLngBounds(sw, ne);

        map = L.map('mapdiv', {
            center: L.latLng(1.2868108, 103.8545349),
            zoom: zoom,
            trackResize: true
        });
        markerGroup = L.layerGroup().addTo(map);
        
        if($('#map-zoom').length > 0){
            map.on('zoomend', function() {
                $("#map-zoom").val(map.getZoom());
            });
    
            $("#map-zoom").val(zoom);
            $("#map-height").val($('#mapdiv').height());
            map.setMaxBounds(bounds);
        }

        let basemap = L.tileLayer('https://www.onemap.gov.sg/maps/tiles/Default/{z}/{x}/{y}.png', {
        detectRetina: true,
        maxZoom: 19,
        minZoom: 11,
        /** DO NOT REMOVE the OneMap attribution below **/
        attribution: '<img src="https://www.onemap.gov.sg/web-assets/images/logo/om_logo.png" style="height:20px;width:20px;"/>&nbsp;<a href="https://www.onemap.gov.sg/" target="_blank" rel="noopener noreferrer">OneMap</a>&nbsp;&copy;&nbsp;contributors&nbsp;&#124;&nbsp;<a href="https://www.sla.gov.sg/" target="_blank" rel="noopener noreferrer">Singapore Land Authority</a>'
        });

        basemap.addTo(map);
    }
    
	// Map initialization
    initDefaultMap(11);
	
	$("#map-zoom-value").text(11);
	
    if($('#map-zoom').length > 0){
        $("#map-zoom").on('change', function(e){
			
			var value = $(this).val();
            map.setZoom(value);
            $("#map-zoom-value").text(value);
        });

        $("#map-height").on('change', function(e){
            $('#mapdiv').height($(this).val());
        });
		
    }

	// Location search calling OneMap API
	//Add code to detect map type in the future, then decide what API to use
    $("#location-search").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: breezyMapAjax.ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'breezy_map_location_search',
                    search_val: request.term,
					nonce: $('#breezy_map_location_nonce').val().trim()
                },
                success: function (data) {
                    if (data.success) {
                        response($.map(data.data, function (item) {
                            return {
                                label: item.SEARCHVAL,
                                value: item.SEARCHVAL,
                                latitude: item.LATITUDE,
                                longitude: item.LONGITUDE
                            };
                        }));
                    } else {
                        response([]);
                    }
                }
            });
        },
        select: function (event, ui) {
            var latitude = ui.item.latitude;
            var longitude = ui.item.longitude;
			// var label = ui.item.label;
            var label = 'Drag this pin to your preferred center location.<br> This will define the center of the map when it loads.';
			
			$('.instruction-msg').hide();

			$('#location-latitude').val(latitude)
			$('#location-longitude').val(longitude)

            // var map = L.map('mapdiv', {
            //     center: L.latLng(latitude, longitude),
            //     zoom: 16
            // });

            // let sw = L.latLng(1.144, 103.535);
            // let ne = L.latLng(1.494, 104.502);
            // let bounds = L.latLngBounds(sw, ne);
            // map.setMaxBounds(bounds);

            // var basemap = L.tileLayer('https://www.onemap.gov.sg/maps/tiles/Default/{z}/{x}/{y}.png', {
            //     detectRetina: true,
            //     maxZoom: 19,
            //     minZoom: 11,
            //     attribution: '<img src="https://www.onemap.gov.sg/web-assets/images/logo/om_logo.png" style="height:20px;width:20px;"/>&nbsp;<a href="https://www.onemap.gov.sg/" target="_blank" rel="noopener noreferrer">OneMap</a>&nbsp;&copy;&nbsp;contributors&nbsp;&#124;&nbsp;<a href="https://www.sla.gov.sg/" target="_blank" rel="noopener noreferrer">Singapore Land Authority</a>'
            // });

            // basemap.addTo(map);
            if (markerGroup) {
                markerGroup.clearLayers();
            }
			map.setView([latitude, longitude], 16);
            
            let marker = L.marker([latitude, longitude],{icon: iconBino, draggable:true}).addTo(markerGroup)
               .bindPopup(label)
               .openPopup().on('drag', function(event) {
                var marker = event.target;
                $('#location-latitude').val(marker._latlng.lat)
                $('#location-longitude').val(marker._latlng.lng)
            });
        }
    });
	
	// Fields error messages
	function showError(field, message) {
		if (!$(field).next('.error-message').length) {
			$(field).after('<div class="error-message" style="color: red;">' + message + '</div>');
		}
	}
	
	// Create map
	$('#create-map').on('click', function(e) {
        e.preventDefault();

        $('.error-message').remove();

		var type = $('#map-type').val().trim();
        var title = $('#map-title').val().trim();
        var address = $('#location-search').val().trim();
        var longitude = $('#location-longitude').val().trim();
        var latitude = $('#location-latitude').val().trim();
		var zoom = $('#map-zoom').val().trim();
		var height = $('#map-height').val().trim();
        var nonce = $('#breezy_map_nonce').val().trim();
        var hasError = false;

        

        // Validate fields
		if (!type) {
            showError('#map-type', 'Map type is required.');
            hasError = true;
        }

        if (!title) {
            showError('#map-title', 'Title is required.');
            hasError = true;
        }

        if (!address) {
            showError('#location-search', 'Center location is required.');
            hasError = true;
        }
		
        if (!zoom) {
            showError('#map-zoom', 'Zoom level is required.');
            hasError = true;
        }
        if (!height) {
            showError('#map-height', 'Map height is required.');
            hasError = true;
        }
		
        if (hasError) {
            return;
        }

        $.ajax({
            url: breezyMapAjax.ajaxurl,
			method: 'POST',
			dataType: 'json',
            data: {
                action: 'breezy_map_create',
				type: type,
                title: title,
                address: address,
                longitude: longitude,
                latitude: latitude,
				zoom: zoom,
				height: height,
                nonce: nonce
            },
            success: function(response) {
                if(response.success) {
					pluginNotification("success", response.data.message)
					loadAllMaps()
                } else {
					pluginNotification("error", response.data.message)
                }
            },
            error: function() {
				pluginNotification("error", 'An error occurred while creating the map.')
            }
        });
    });
	
	
	//Load all maps on initial page load
	var mapObj, mapObjMarkerGroup;
	loadAllMaps(mapObj)
	
	// Notification box to show success/error messages
	function pluginNotification(type, message) {
		$('.notification-wrapper').addClass("active")
		if (type == "success") {
			$('.notification-wrapper .message').text(message)
		} else if (type == "error") {
			$('.notification-wrapper .message').text(message)
		}
	
		$('.notification-wrapper .close-btn').unbind().bind('click', function(){
			$('.notification-wrapper').removeClass("active")
		})	
	}
	
	// Function to load all maps
	function loadAllMaps() {
		$.ajax({
            url: breezyMapAjax.ajaxurl,
			method: 'POST',
			dataType: 'json',
            data: {
                action: 'breezy_map_load_maps'
            },
            success: function(response) {
				$('#maps-table').find('tbody').html('');
                if(response.success) {
                    
					var mapTypeTitles = {
						'one_map': 'One Map',
					};

					var rowsHTML = ""
					response.data.maps.forEach(function(map) {
						
						let shortcode = '[breezy_map id="' + map.map_id + '"]'

						var mapType = mapTypeTitles[map.map_type] || 'Unknown';
						rowsHTML += '<tr><td>' + map.map_title + '</td><td><span class="shortcode copy-shortcode-btn">' + shortcode + '</span><span class="tooltip-text">Copied!</span></td><td>' + mapType + '</td><td class="action-wrapper"><div class="manage-markers-btn" data-map-id="' + map.map_id + '"></div><div class="delete-map-btn" data-map-id="' + map.map_id + '"></div></td><td>' + map.created_date + '</td></tr>'
						
					})
					
					//Inject
					$('#maps-table').find('tbody').html(rowsHTML)
					
					
					//Delete
					$('.delete-map-btn').unbind().bind('click', function(e) {
						e.preventDefault();

						var nonce = $('#breezy_map_remove_map_nonce').val();
						var mapId = $(this).attr('data-map-id')
						
						var confirmDelete = window.confirm("Are you sure you want to delete this map and all associated markers? This action cannot be undone.");

						if (confirmDelete) {
							$.ajax({
								url: breezyMapAjax.ajaxurl,
								method: 'POST',
								dataType: 'json',
								data: {
									action: 'breezy_map_remove_map',
									map_id: mapId,
									nonce: nonce
								},
								success: function(response) {
									if(response.success) {
										pluginNotification("success", response.data.message)
										loadAllMaps()
									} else {
										pluginNotification("error", response.data.message)
									}
								},
								error: function() {
									pluginNotification("error", 'An error occurred while deleting the map.');
								}
							});
						}
						
						
					});
					
					//Copy
					$('.copy-shortcode-btn').unbind().bind('click', function(e) {
						e.preventDefault();

						var shortcode = $(this).text();
						var $temp = $("<input>");
						$("body").append($temp);
						$temp.val(shortcode).select();
						document.execCommand("copy");
						$temp.remove();
											
						var $tooltip = $(this).next('.tooltip-text');
						$tooltip.addClass("active");

						setTimeout(function() {
							$tooltip.removeClass("active");
						}, 1000);

					});
					
					
					//Manage
					manageMarker();
					
                } 
            },
            error: function() {
                pluginNotification("error", 'An error occurred while loading maps.');
            }
        });
	}
	
	// Function to load all location by map post id
	function loadAllMarkers(mapId) {
		
		$.ajax({
			url: breezyMapAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'breezy_map_load_map_data',
				map_id: mapId,
				nonce: $('#breezy_map_load_map_data_nonce').val().trim()
			},
			success: function(response) {
				if(response.success) {
					$('#edit-map-wrapper').hide();
					$('#marker-form').show();
					$('.map-edit-field-wrapper').hide();
					$('.map-marker-edit-field-wrapper').show();
					$('.breezy-map-wrapper .left-wrapper h3').text('Add Marker to Map');
					$('#marker-map-title').attr('data-id', mapId);

					var latitude = response.data.latitude;
					var longitude = response.data.longitude;
					var address = response.data.address;
					var title = response.data.title;
					var zoom = response.data.zoom;
					var height = response.data.height;
					
					$('#marker-map-title').val(title);
					$('#marker-map-zoom').val(zoom);
					$('#marker-map-zoom-value').html(zoom);
					$('#marker-map-height').val(height);
					$('#marker-map-location-search').val(address);
					$('#marker-map-location-latitude').val(latitude);
					$('#marker-map-location-longitude').val(longitude);
					
					
					$('.map-title-edit-btn').on('click', function(e){
						e.preventDefault();
						$(this).hide()
						$('.update-map-title-btn-wrapper').show();
						$('#marker-map-title').attr('disabled', false)
						//$('#marker-form .map-edit-field-wrapper').show();

					})
					
					$('.update-map-title-btn').on('click', function(e){

						
						var updatedTitle = $('#marker-map-title').val()
						
						if (updatedTitle == "") {
							//add error notification
							return
						}
						
						$.ajax({
							url: breezyMapAjax.ajaxurl,
							type: 'POST',
							data: {
								action: 'breezy_map_update_title',
								map_id: mapId,
								title: updatedTitle,
								nonce: $('#breezy_map_save_map_title_nonce').val()
							},
							success: function(response) {
								if(response.success) {
									pluginNotification("success", response.data.message)
									
									loadAllMarkers(mapId)
									
									$('.map-title-edit-btn').show()
									$('.update-map-title-btn-wrapper').hide();
									$('#marker-map-title').attr('disabled', true)
									
								} else {
									pluginNotification("error", response.data.message)
								}
							},
							error: function() {
								pluginNotification("error", 'An error occurred while removing the marker.')
							}
						});

					});
					
					var address = response.data.address;
					var markers = [];

					if ($('.breezy-map-wrapper .right-wrapper #mapdivadd').hasClass("leaflet-container")) {  
						if(mapObj){

							mapObj.off();
							mapObj.remove();
							mapObj = null;
						}
					} 
					mapObj = L.map('mapdivadd', {
						center: L.latLng(latitude, longitude),
						zoom: zoom
					});
					
					
					
					mapObjMarkerGroup = L.layerGroup().addTo(mapObj);
					var basemap = L.tileLayer('https://www.onemap.gov.sg/maps/tiles/Default/{z}/{x}/{y}.png', {
						detectRetina: true,
						maxZoom: 19,
						minZoom: 11,
						attribution: '<img src="https://www.onemap.gov.sg/web-assets/images/logo/om_logo.png" style="height:20px;width:20px;"/>&nbsp;<a href="https://www.onemap.gov.sg/" target="_blank" rel="noopener noreferrer">OneMap</a>&nbsp;&copy;&nbsp;contributors&nbsp;&#124;&nbsp;<a href="https://www.sla.gov.sg/" target="_blank" rel="noopener noreferrer">Singapore Land Authority</a>'
					});

					basemap.addTo(mapObj);

					// Add the main map marker
					var mainMarker = L.marker([latitude, longitude],{icon: iconBino, id: 'marker-main' }).bindPopup(title)
						.openPopup();
					mapObjMarkerGroup.addLayer(mainMarker);
					markers.push({
						markerId: mapId,
						marker: mainMarker
					});

					// Add the main map marker to list
					var markerList = `
						<tr>
							<th>Marker title</th>
							<th>Marker description</th>
							<th>Location</th>
							<th>Latitude</th>
							<th>Longitude</th>
							<th>Actions</th>
						</tr>
						<tr>
							<td>${title}</td>
							<td>${'Center location'}</td>
							<td>${address}</td>
							<td>${latitude}</td>
							<td>${longitude}</td>
							<td><div class="edit-marker-btn main-marker" data-id="${mapId}"></div></td>

						</tr>
					`;

					// Loop through and add each marker associated with the map
					
					response.data.markers.forEach(function(marker) {
						
						var popupContent = `
							<div class="breezy-map-marker-info-wrapper">
								<div class="flex-wrapper">
									<div class="left-wrapper">
										<img src="${marker.marker_image}" alt="${marker.marker_address}" />
									</div>
									<div class="right-wrapper">
										<h4>${marker.marker_title}</h4>
										<p>${marker.marker_address}</p>
										<p>${marker.marker_description}</p>
									</div>
								</div>
							</div>
						`;

						var mapMarker = L.marker([marker.marker_latitude, marker.marker_longitude],{draggable:false}).bindPopup(popupContent)
							.openPopup();

						mapObjMarkerGroup.addLayer(mapMarker);
							
						markers.push({
							markerId: marker.post_id,
							marker: mapMarker
						});
						
						markerList += `<tr data-id="${marker.post_id}">
							<td class="marker-title"><a href="javascript:void(0)"><span class="title">${marker.marker_title}</span><span class="locate-marker"></span></a></td>
							<td>${marker.marker_description}</td>
							<td>${marker.marker_address}</td>
							<td class="lat">${marker.marker_latitude}</td>
							<td class="lng">${marker.marker_longitude}</td>
							<td><div class="edit-marker-btn" data-id="${marker.post_id}"></div><div class="remove-marker-btn" data-id="${marker.post_id}"></div></td>
						</tr>`;
					});
					
					$('#list-of-markers').html(markerList);
					let currentMarker = null;
					$('.edit-marker-btn').on('click', function(e) {
						e.preventDefault();
						$('html, body').animate({
							scrollTop: $('#marker-title').offset().top - 200
						}, 500); // 500ms animation duration
						const markerId = $(this).data('id');
	
						const marker = response.data.markers.find(m => m.post_id == markerId);

						//main-marker
						if($(this).hasClass('main-marker')){
							$('.map-edit-field-wrapper').show();
							$('.map-marker-edit-field-wrapper').hide();
							
							if($('#marker-map-zoom').length > 0){
								$("#marker-map-zoom").on('change', function(e){
									
									var value = $(this).val();
									mapObj.setZoom(value);
									$("#marker-map-zoom-value").text(value);
								});
						
								$("#marker-map-height").on('change', function(e){
									$('#mapdivadd').height($(this).val());
								});

								mapObj.on('zoomend', function() {
									$("#marker-map-zoom").val(mapObj.getZoom());
									$("#marker-map-zoom-value").text(mapObj.getZoom());
								});
								$('#mapdivadd').height($("#marker-map-height").val());
								
							}

							mapObj.setView([latitude, longitude], zoom);
							$('.breezy-map-wrapper .left-wrapper h3').text('Edit Main Marker');
							//set marker draggable
							if (currentMarker) {
								currentMarker.dragging.disable(); // Disable draggable on any previously edited marker
							}
							currentMarker = markers.find(m => m.markerId == markerId).marker;
							currentMarker.dragging.enable(); // Enable draggable for the current marker
							currentMarker.on('dragend', function(event) {
								var position = currentMarker.getLatLng();
								$('#marker-map-location-latitude').val(position.lat);
								$('#marker-map-location-longitude').val(position.lng);
							});

							$('#map-edit-save').on('click',function() {
								e.preventDefault();

								$('.error-message').remove();
								var type = $('#marker-map-type').val().trim();
								var address = $('#marker-map-location-search').val().trim();
								var longitude = $('#marker-map-location-longitude').val().trim();
								var latitude = $('#marker-map-location-latitude').val().trim();
								var zoom = $('#marker-map-zoom').val().trim();
								var height = $('#marker-map-height').val().trim();
								var nonce = $('#breezy_map_save_map_title_nonce').val().trim();
								var hasError = false;

								if (!type) {
									showError('#marker-map-type', 'Map type is required.');
									hasError = true;
								}
								if (!address) {
									showError('#marker-map-location-search', 'Center location is required.');
									hasError = true;
								}
								
								if (!zoom) {
									showError('#marker-map-zoom', 'Zoom level is required.');
									hasError = true;
								}
								if (!height) {
									showError('#marker-map-height', 'Map height is required.');
									hasError = true;
								}
								
								if (hasError) {
									return;
								}

								$.ajax({
									url: breezyMapAjax.ajaxurl,
									method: 'POST',
									dataType: 'json',
									data: {
										action: 'breezy_map_update',
										map_id: mapId,
										type: type,
										title: title,
										address: address,
										longitude: longitude,
										latitude: latitude,
										zoom: zoom,
										height: height,
										nonce: nonce
									},
									success: function(response) {
										if(response.success) {
											pluginNotification("success", response.data.message)
											loadAllMaps()
										} else {
											pluginNotification("error", response.data.message)
										}
										loadAllMarkers(mapId);

									},
									error: function() {
										pluginNotification("error", 'An error occurred while updating the map.')
									}
									
								});
							});
						}
						//marker
						else {
							$('.map-edit-field-wrapper').hide();
							$('.map-marker-edit-field-wrapper').show();
							
							
							if (marker) {
								$('#marker-title').val(marker.marker_title);
								tinymce.get('marker_description').setContent(marker.marker_description);
								if(marker.marker_image){
									$('#marker-image').val(marker.marker_image);
									$('#marker-image-preview').attr('src', marker.marker_image).show();
								}
								$('#marker-search').val(marker.marker_address);
								$('#marker-latitude').val(marker.marker_latitude);
								$('#marker-longitude').val(marker.marker_longitude);
								
								$('#marker-id').val(markerId);
								mapObj.setView([marker.marker_latitude,marker.marker_longitude], 16);
								$('.breezy-map-wrapper .left-wrapper h3').text('Edit Marker');
								//set marker draggable
								if (currentMarker) {
									currentMarker.dragging.disable(); // Disable draggable on any previously edited marker
								}
								currentMarker = markers.find(m => m.markerId == markerId).marker;
								currentMarker.dragging.enable(); // Enable draggable for the current marker
								
								// Update the latitude and longitude fields when marker is dragged
								currentMarker.on('dragend', function(event) {
									var position = currentMarker.getLatLng();
									$('#marker-latitude').val(position.lat);
									$('#marker-longitude').val(position.lng);
								});
	
							}
						}
						
						
					});



					$('.marker-title a').on('click', function(e){ 
						e.preventDefault();
						var lat = $(this).parent('td').siblings('td.lat').text();
						var lng = $(this).parent('td').siblings('td.lng').text();

						
						let markerId = $(this).parent('td').parent('tr').data('id');
						const marker = markers.find(m => m.markerId == markerId);

						marker.marker.openPopup();
						mapObj.setView([lat,lng], 16);
					});

					//Bind remove function
					$('.remove-marker-btn').on('click', function(e) {
						e.preventDefault();
						
						var $this = $(this);
						var markerId = $this.data('id');
						$this.hide();
						// Check if confirmation buttons already exist, if not, create them
						//if (!$this.next('.confirmation-buttons').length) {
							
								
							var confirmDelete = window.confirm("Are you sure you want to delete this marker?");

							if (confirmDelete) {
								
								
								var markerObj = markers.find(function(markerObj) {
									return markerObj.markerId === markerId;
								});
					
								if (markerObj) {
									var mapId = $('#marker-map-title').attr('data-id');
									$.ajax({
										url: breezyMapAjax.ajaxurl,
										type: 'POST',
										data: {
											action: 'breezy_map_remove_marker',
											map_id: mapId,
											marker_id: markerId,
											nonce: $('#breezy_map_remove_marker_nonce').val()
										},
										success: function(response) {
											if(response.success) {
												pluginNotification("success", response.data.message)

												mapObj.removeLayer(markerObj.marker); // Remove marker from map
												markers = markers.filter(function(markerObj) {
													return markerObj.markerId !== markerId;
												}); // Remove marker from markers array

												$this.parent('td').parent('tr').remove(); // Remove row from list
											} else {
												pluginNotification("error", response.data.message)
											}
										},
										error: function() {
											pluginNotification("error", 'An error occurred while removing the marker.')
										}
									});

								}

								$(this).parent('.confirmation-buttons').remove();
							}
							//});
					
							// Handle No button click
							//$this.next('.confirmation-buttons').find('.confirm-no').on('click', function() {
							//	$this.show();
							//	$(this).parent('.confirmation-buttons').remove();
							//});
						//}
					});
					
					$('#save-marker').unbind().bind('click', function(e) {
							e.preventDefault();
							$('.error-message').remove();
							var mapId = $('#marker-map-title').attr('data-id');
							var mapTitle = $('#marker-map-title').val();
							var latitude = $('#marker-latitude').val();
							var longitude = $('#marker-longitude').val();
							var markerLabel = $('#marker-title').val();
							var markerAddress = $('#marker-search').val();
							var markerPostId = $('#marker-id').val();
							var hasError = false;
							//var description = $('#marker-description').val();
							
							
							var description = tinymce.get('marker_description').getContent();
							
							var myimage = $('#marker-image').val();
							// Validate fields
							if (!markerLabel) {
								showError('#marker-title', 'Title is required');
								hasError = true;
							}

							if (!markerAddress || !latitude || !longitude) {
								showError('#marker-search', 'Select a location');
								hasError = true;
							}
							
							if (hasError) {
								return;
							}

							var action = (markerPostId === '' || markerPostId === null) ? 'breezy_map_save_marker' : 'breezy_map_update_marker';

							$.ajax({
								url: breezyMapAjax.ajaxurl,
								type: 'POST',
								data: {
									action: action,
									map_id: mapId,
									mapTitle: mapTitle,
									latitude: latitude,
									longitude: longitude,
									label: markerLabel,
									address: markerAddress,
									description: description,
									image: myimage,
									nonce: $('#breezy_map_save_marker_nonce').val(),
									markerPostId: markerPostId
								},
								success: function(response) {
									if(response.success) {
										//alert(response.data.message);
										// Optionally, refresh the markers list or redirect
										$('#marker-title').val('');
										tinymce.get('marker_description').setContent('');
										$('#marker-image').val('');
										$('#marker-image-preview').hide();
										$('#marker-search').val('');
										$('#marker-latitude').val('');
										$('#marker-longitude').val('');
										$('#marker-id').val('');
										$('.breezy-map-wrapper .left-wrapper h3').text('Add Marker to Map');

										if (currentMarker) {
											currentMarker.dragging.disable();
											currentMarker = null; // Reset current marker reference
										}

										loadAllMarkers(mapId);
										pluginNotification("success", response.data.message)
										
									} else {
										pluginNotification("error", response.data.message)
									}
								},
								error: function() {
									pluginNotification("error", 'An error occurred while saving the marker.')
								}
							});
						});
					
					$('#marker-search').off('autocomplete');						
					$('#marker-search').autocomplete({
							source: function (request, response) {
								$.ajax({
									url: breezyMapAjax.ajaxurl,
									method: 'POST',
									dataType: 'json',
									data: {
										action: 'breezy_map_location_search',
										search_val: request.term,
										nonce: $('#breezy_map_location_nonce').val().trim()
									},
									success: function (data) {
										if (data.success) {
											response($.map(data.data, function (item) {
												return {
													label: item.SEARCHVAL,
													value: item.SEARCHVAL,
													latitude: item.LATITUDE,
													longitude: item.LONGITUDE
												};
											}));
										} else {
											response([]);
										}
									}
								});
							},
							select: function(event, ui) {
								var latitude = ui.item.latitude;
								var longitude = ui.item.longitude;
								var address = ui.item.label;

								$('#marker-latitude').val(latitude);
								$('#marker-longitude').val(longitude);
								var title = $('#marker-title').val();
								//var description = $('#marker-description').val();
								var description = tinymce.get('marker_description').getContent();
								var image = $('#marker-image').val();


								var leftHtmlString = ""
								var oneColClass = ""
								
								if (image !== "") {
									leftHtmlString = `<div class="left-wrapper"><img src="${image}" alt="${address}" /></div>`
								} else {
									oneColClass = "one-col-wrapper"
								}
								
								var rightHtmlString = ""
								if (title !== "") {
									rightHtmlString += `<h4>${title}</h4>`
								}
								if (address !== "") {
									rightHtmlString += `<p>${address}</p>`
								}
								if (description !== "") {
									rightHtmlString += `<p>${description}</p>`
								}

								var popupContent = `
									<div class="breezy-map-marker-info-wrapper">
										<div class="flex-wrapper ${oneColClass}">
											${leftHtmlString}
											<div class="right-wrapper">${rightHtmlString}</div>
										</div>
									</div>
								`;
								
								if(markerGroup) {
									markerGroup.clearLayers();
								}

								mapObj.setView([latitude, longitude], 16);
								
								let marker = L.marker([latitude, longitude],{draggable:true}).addTo(markerGroup)
								.bindPopup(popupContent)
								.openPopup().on('drag', function(event) {
									var marker = event.target;
									$('#location-latitude').val(marker._latlng.lat)
									$('#location-longitude').val(marker._latlng.lng)
								});
								
								 L.marker([latitude, longitude]).addTo(mapObj)
								 	.bindPopup(popupContent)
								 	.openPopup();
							}
						});
					

				} else {
					pluginNotification("success", 'Failed to load the marker form.')
				}
			},
			error: function() {
				pluginNotification("success", 'An error occurred while processing the request.')
			}
		});
	}
	
	// Manage locations click event
	function manageMarker() {
		
		$('.manage-markers-btn').unbind().bind('click', function(e) {
			e.preventDefault();

			var mapId = $(this).data('map-id');
	
			loadAllMarkers(mapId)
	
			
		});
		
	}
	
    
	//Marker management fields
	var mediaUploader;

	$('#upload-image-button').click(function(e) {
		e.preventDefault();
		
		if (mediaUploader) {
			mediaUploader.open();
			return;
		}
		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: 'Choose Image',
			button: {
				text: 'Choose Image'
			},
			multiple: false
		});

		mediaUploader.on('select', function() {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#marker-image').val(attachment.url);
			$('#marker-image-preview').attr('src', attachment.url).show();
			$('#clear-image-button').show();
		});

		mediaUploader.open();
	});
	
	// Clear uploaded image
    $('#clear-image-button').on('click', function() {
        $('#marker-image').val('');
        $('#marker-image-preview').attr('src', '').hide();
        $('#clear-image-button').hide();
    });
	
	// Back to maps listing 
    $('#back-to-maps').on('click', function(e) {
        e.preventDefault();
        $('#marker-form').hide();
        $('#edit-map-wrapper').show();
		
		//Disabled the map title field
		$('.map-title-edit-btn').show()
		$('.update-map-title-btn-wrapper').hide();
		$('#marker-map-title').attr('disabled', true)

		//Clear marker fields
		$('#marker-title').val('');
		tinymce.get('marker_description').setContent('');
		$('#marker-image').val('');
		$('#marker-image-preview').hide();
		$('#marker-search').val('');
		$('#marker-latitude').val('');
		$('#marker-longitude').val('');
		$('#marker-id').val('');
		$('.breezy-map-wrapper .left-wrapper h3').text('Add Marker to Map');
	
		//Reinit map

		if ($('.breezy-map-wrapper .right-wrapper #mapdivadd').hasClass("leaflet-container")) {  
			if(mapObj){

				mapObj.off();
				mapObj.remove();
				mapObj = null;
			}
		} 
		initDefaultMap(11);
		
		//Reload map table
		loadAllMaps()
    });
	
   
	
});