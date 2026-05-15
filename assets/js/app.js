(function () {
    var map = null;
    var markers = [];
    var infoWindow = null;
    var manualMarker = null;
    var manualMode = false;
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

        kakao.maps.event.addListener(map, 'click', function (mouseEvent) {
            if (!manualMode) {
                return;
            }
            addManualPlace(mouseEvent.latLng);
        });

        setTimeout(function () {
            map.relayout();
            map.setCenter(new kakao.maps.LatLng(defaultCenter.lat, defaultCenter.lng));
        }, 0);
    }

    function renderResults(places, totalCount) {
        var list = $('#place-results');
        list.innerHTML = '';
        updateResultCount(totalCount || places.length);

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

        });
    }

    function focusPlace(index, place) {
        manualMode = false;
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

        updateMapActionPanel(place.place_name, place.road_address_name || place.address_name || '', false);
    }

    function addManualPlace(position) {
        if (!map || !window.kakao || !window.kakao.maps) {
            return;
        }

        if (!manualMarker) {
            manualMarker = new kakao.maps.Marker({
                map: map,
                position: position,
                zIndex: 30
            });
        } else {
            manualMarker.setPosition(position);
            manualMarker.setMap(map);
        }

        map.setCenter(position);
        updateMapActionPanel(
            '수동 지정 장소',
            '위도 ' + position.getLat().toFixed(6) + ', 경도 ' + position.getLng().toFixed(6),
            true
        );
    }

    function updateMapActionPanel(name, address, isManual) {
        var panel = $('#map-action-panel');
        var title = $('#selected-place-name');
        var description = $('#selected-place-address');

        if (!panel || !title || !description) {
            return;
        }

        panel.classList.remove('is-empty');
        title.textContent = name;
        description.textContent = isManual
            ? address + ' 지점을 기준으로 직접 장소를 등록할 수 있습니다.'
            : address || '주소 정보가 없는 장소입니다.';
    }

    function updateResultCount(count) {
        var countEl = $('#result-count');
        if (!countEl) {
            return;
        }

        countEl.textContent = '총 ' + Number(count || 0).toLocaleString('ko-KR') + '개';
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
            updateResultCount(0);

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
                    renderResults(data.documents || [], data.meta ? data.meta.total_count : 0);
                })
                .catch(function (error) {
                    list.innerHTML = '<li class="empty">' + escapeHtml(error.message) + '</li>';
                    updateResultCount(0);
                });
        });
    }

    function bindMapActions() {
        var manualButton = $('[data-action="manual-place-mode"]');
        var noteButton = $('[data-action="open-note-modal"]');
        var modal = $('#place-note-modal');

        if (manualButton) {
            manualButton.addEventListener('click', function () {
                manualMode = true;
                var panel = $('#map-action-panel');
                var title = $('#selected-place-name');
                var description = $('#selected-place-address');

                if (panel && title && description) {
                    panel.classList.add('is-manual-mode');
                    panel.classList.remove('is-empty');
                    title.textContent = '지도에서 위치를 클릭하세요';
                    description.textContent = '검색에 없는 가게도 지도에서 직접 지정할 수 있습니다.';
                }
            });
        }

        if (noteButton && modal) {
            noteButton.addEventListener('click', function () {
                modal.hidden = false;
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal || event.target.closest('[data-action="close-note-modal"]')) {
                    modal.hidden = true;
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMap();
        bindSearch();
        bindMapActions();
    });
})();
