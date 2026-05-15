(function () {
    var map = null;
    var markers = [];
    var infoWindow = null;
    var defaultCenter = { lat: 37.566826, lng: 126.9786567 };

    function $(selector) {
        return document.querySelector(selector);
    }

    function clearMarkers() {
        markers.forEach(function (marker) {
            marker.setMap(null);
        });
        markers = [];
    }

    function initMap() {
        var mapEl = $('#map');
        if (!window.TASTEMAP_HAS_KAKAO || !window.kakao || !window.kakao.maps) {
            return;
        }

        mapEl.innerHTML = '';
        map = new kakao.maps.Map(mapEl, {
            center: new kakao.maps.LatLng(defaultCenter.lat, defaultCenter.lng),
            level: 5
        });
        infoWindow = new kakao.maps.InfoWindow({ zIndex: 10 });
        mapEl.classList.add('is-ready');

        kakao.maps.event.addListener(map, 'tilesloaded', function () {
            mapEl.classList.add('has-tiles');
        });

        setTimeout(function () {
            map.relayout();
            map.setCenter(new kakao.maps.LatLng(defaultCenter.lat, defaultCenter.lng));
        }, 0);
    }

    function renderResults(places) {
        var list = $('#place-results');
        list.innerHTML = '';

        if (!places.length) {
            list.innerHTML = '<li class="empty">검색 결과가 없습니다.</li>';
            return;
        }

        clearMarkers();

        places.forEach(function (place, index) {
            var item = document.createElement('li');
            item.className = 'place-item';
            item.tabIndex = 0;
            item.setAttribute('role', 'button');
            item.innerHTML =
                '<strong>' + escapeHtml(place.place_name) + '</strong>' +
                '<span>' + escapeHtml(place.road_address_name || place.address_name || '') + '</span>' +
                '<small>' + escapeHtml(place.category_name || '') + '</small>' +
                '<div class="place-actions">' +
                    '<a href="' + escapeHtml(place.place_url) + '" target="_blank" rel="noreferrer" data-skip-focus="true">카카오맵에서 보기</a>' +
                    '<button type="button" data-action="save-place" data-skip-focus="true">저장</button>' +
                '</div>';
            list.appendChild(item);

            if (map && place.y && place.x) {
                var position = new kakao.maps.LatLng(place.y, place.x);
                var marker = new kakao.maps.Marker({
                    map: map,
                    position: position
                });
                markers.push(marker);

                kakao.maps.event.addListener(marker, 'click', function () {
                    focusPlace(index, place);
                });

                if (index === 0) {
                    map.setCenter(position);
                }
            }

            item.addEventListener('click', function (event) {
                if (event.target.closest('[data-skip-focus="true"]')) {
                    return;
                }
                focusPlace(index, place);
            });

            item.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }
                event.preventDefault();
                focusPlace(index, place);
            });

            var saveButton = item.querySelector('[data-action="save-place"]');
            saveButton.addEventListener('click', function () {
                focusPlace(index, place);
            });
        });
    }

    function focusPlace(index, place) {
        var list = $('#place-results');
        var items = list ? list.querySelectorAll('.place-item') : [];
        items.forEach(function (item) {
            item.classList.remove('is-active');
        });

        if (items[index]) {
            items[index].classList.add('is-active');
        }

        markers.forEach(function (marker, markerIndex) {
            if (typeof marker.setZIndex === 'function') {
                marker.setZIndex(markerIndex === index ? 20 : 1);
            }
        });

        if (!map || !place.y || !place.x) {
            return;
        }

        var position = new kakao.maps.LatLng(place.y, place.x);
        map.setCenter(position);
        if (typeof map.setLevel === 'function') {
            map.setLevel(4);
        }

        if (infoWindow && markers[index]) {
            infoWindow.setContent(
                '<div class="map-info-window">' +
                    '<strong>' + escapeHtml(place.place_name) + '</strong>' +
                    '<span>' + escapeHtml(place.road_address_name || place.address_name || '') + '</span>' +
                '</div>'
            );
            infoWindow.open(map, markers[index]);
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function bindSearch() {
        var form = $('#place-search-form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var keyword = $('#keyword').value.trim();
            var category = $('#category').value;
            var list = $('#place-results');
            list.innerHTML = '<li class="empty">검색 중입니다...</li>';

            fetch('api/place_search.php?query=' + encodeURIComponent(keyword) + '&category=' + encodeURIComponent(category))
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) {
                            throw new Error(data.error || '장소 검색에 실패했습니다.');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    renderResults(data.documents || []);
                })
                .catch(function (error) {
                    list.innerHTML = '<li class="empty">' + escapeHtml(error.message) + '</li>';
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMap();
        bindSearch();
    });
})();
