(function () {
    var map = null;
    var markers = [];
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

        map = new kakao.maps.Map(mapEl, {
            center: new kakao.maps.LatLng(defaultCenter.lat, defaultCenter.lng),
            level: 5
        });
        mapEl.classList.add('is-ready');
        mapEl.innerHTML = '';
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
            item.innerHTML =
                '<strong>' + escapeHtml(place.place_name) + '</strong>' +
                '<span>' + escapeHtml(place.road_address_name || place.address_name || '') + '</span>' +
                '<small>' + escapeHtml(place.category_name || '') + '</small>' +
                '<a href="' + escapeHtml(place.place_url) + '" target="_blank" rel="noreferrer">카카오맵에서 보기</a>';
            list.appendChild(item);

            if (map && place.y && place.x) {
                var position = new kakao.maps.LatLng(place.y, place.x);
                var marker = new kakao.maps.Marker({
                    map: map,
                    position: position
                });
                markers.push(marker);

                if (index === 0) {
                    map.setCenter(position);
                }
            }
        });
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

