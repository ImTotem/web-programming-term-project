(function () {
    var map = null;
    var markers = [];
    var infoWindow = null;
    var manualMarker = null;
    var manualMode = false;
    var selectedPlace = null;
    var selectedIndex = null;
    var selectedPhotoFiles = [];
    var photoPreviewUrls = [];
    var draggedPhotoIndex = null;
    var longPressTimer = null;
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
        clearPlaceSelection();
        clearMarkers();

        if (!places.length) {
            list.innerHTML = '<li class="empty">검색 결과가 없습니다.</li>';
            return;
        }

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
                    selectPlace(index, place);
                });

                if (index === 0) {
                    map.setCenter(position);
                }
            }

            item.addEventListener('click', function (event) {
                if (event.target.closest('[data-skip-focus="true"]')) {
                    return;
                }
                togglePlaceSelection(index, place);
            });

            item.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }
                event.preventDefault();
                togglePlaceSelection(index, place);
            });

        });
    }

    function togglePlaceSelection(index, place) {
        if (selectedIndex === index) {
            clearPlaceSelection();
            return;
        }

        selectPlace(index, place);
    }

    function selectPlace(index, place) {
        selectedIndex = index;
        selectedPlace = place;
        focusPlace(index, place);
    }

    function clearPlaceSelection() {
        manualMode = false;
        var list = $('#place-results');
        var items = list ? list.querySelectorAll('.place-item') : [];
        items.forEach(function (item) {
            item.classList.remove('is-active');
        });

        markers.forEach(function (marker) {
            if (typeof marker.setZIndex === 'function') {
                marker.setZIndex(1);
            }
        });

        if (infoWindow && typeof infoWindow.close === 'function') {
            infoWindow.close();
        }

        selectedIndex = null;
        selectedPlace = null;
        updateFabState();
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

        updateFabState(place);
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
        selectedPlace = {
            id: 'manual_' + position.getLat().toFixed(6) + '_' + position.getLng().toFixed(6),
            place_name: '수동 지정 장소',
            road_address_name: '위도 ' + position.getLat().toFixed(6) + ', 경도 ' + position.getLng().toFixed(6),
            address_name: '',
            category_name: '수동 추가',
            phone: '',
            place_url: '',
            x: String(position.getLng()),
            y: String(position.getLat()),
            is_manual: true
        };
        selectedIndex = null;
        updateFabState(selectedPlace);
    }

    function updateFabState(place) {
        var dock = $('#map-fab-dock');
        var fab = $('[data-action="primary-map-action"]');
        var symbol = $('#map-fab-symbol');
        var quickActions = $('.record-quick-actions');

        if (!dock || !fab || !symbol || !quickActions) {
            return;
        }

        var activePlace = place || selectedPlace;
        var hasRecord = !!(activePlace && (activePlace.has_record || activePlace.record_id));

        quickActions.hidden = !hasRecord;

        if (!activePlace) {
            dock.dataset.mode = 'manual';
            fab.setAttribute('aria-label', '지도에서 직접 추가');
            symbol.textContent = '+';
            return;
        }

        dock.dataset.mode = hasRecord ? 'recorded' : 'record';
        fab.setAttribute('aria-label', hasRecord ? '기록 보기' : '기록 작성');
        symbol.textContent = hasRecord ? '⋯' : '✎';
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
        var primaryButton = $('[data-action="primary-map-action"]');
        var addNoteButton = $('[data-action="add-note"]');
        var editNoteButton = $('[data-action="edit-note"]');
        var modal = $('#place-note-modal');

        if (primaryButton) {
            primaryButton.addEventListener('click', function () {
                if (selectedPlace) {
                    openNoteModal();
                    return;
                }
                manualMode = true;
                primaryButton.setAttribute('aria-label', '지도에서 위치를 클릭하세요');
            });
        }

        if (addNoteButton) {
            addNoteButton.addEventListener('click', openNoteModal);
        }

        if (editNoteButton) {
            editNoteButton.addEventListener('click', openNoteModal);
        }

        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal || event.target.closest('[data-action="close-note-modal"]')) {
                    modal.hidden = true;
                }
            });
        }
    }

    function bindNoteForm() {
        var form = $('.note-form');
        var saveButton = $('[data-action="save-note"]');

        if (!form || !saveButton) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!selectedPlace) {
                alert('기록할 장소를 먼저 선택하세요.');
                return;
            }

            var formData = new FormData(form);
            appendPlaceToFormData(formData, selectedPlace);

            saveButton.disabled = true;
            saveButton.textContent = '저장 중';

            fetch('api/save_note.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || !data.ok) {
                            throw new Error(data.error || '기록 저장에 실패했습니다.');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    selectedPlace.has_record = true;
                    selectedPlace.record_id = data.visit_id;
                    updateFabState(selectedPlace);
                    resetNoteForm(form);
                    closeNoteModal();
                    alert('미식 기록을 저장했습니다.');
                })
                .catch(function (error) {
                    alert(error.message);
                })
                .finally(function () {
                    saveButton.disabled = false;
                    saveButton.textContent = '기록 저장';
                });
        });
    }

    function appendPlaceToFormData(formData, place) {
        formData.set('kakao_place_id', place.id || '');
        formData.set('place_name', place.place_name || '수동 지정 장소');
        formData.set('category_name', place.category_name || '');
        formData.set('address_name', place.address_name || '');
        formData.set('road_address_name', place.road_address_name || '');
        formData.set('phone', place.phone || '');
        formData.set('place_url', place.place_url || '');
        formData.set('latitude', place.y || '');
        formData.set('longitude', place.x || '');
    }

    function resetNoteForm(form) {
        form.reset();
        selectedPhotoFiles = [];
        syncPhotoInput($('#note-photo'));
        showPhotoPreview([]);
        setRatingValue(0, $('#note-rating'), $('#note-rating-label'), $('.star-rating'));
    }

    function closeNoteModal() {
        var modal = $('#place-note-modal');
        if (modal) {
            modal.hidden = true;
        }
    }

    function bindFileUpload() {
        var input = $('#note-photo');
        var uploadButton = $('.file-upload-button');
        var previewList = $('#note-photo-preview-list');

        if (!input || !uploadButton || !previewList) {
            return;
        }

        bindPhotoPreviewActions(input, previewList);

        input.addEventListener('change', function () {
            appendSelectedPhotos(input.files, input);
        });

        uploadButton.addEventListener('dragover', function (event) {
            event.preventDefault();
            uploadButton.classList.add('is-dragging');
        });

        uploadButton.addEventListener('dragleave', function () {
            uploadButton.classList.remove('is-dragging');
        });

        uploadButton.addEventListener('drop', function (event) {
            event.preventDefault();
            uploadButton.classList.remove('is-dragging');

            if (!event.dataTransfer || !event.dataTransfer.files.length) {
                return;
            }

            appendSelectedPhotos(event.dataTransfer.files, input);
        });
    }

    function appendSelectedPhotos(files, input) {
        var incomingFiles = Array.prototype.slice.call(files || []);

        if (!incomingFiles.length) {
            return;
        }

        selectedPhotoFiles = selectedPhotoFiles.concat(incomingFiles);
        syncPhotoInput(input);
        showPhotoPreview(selectedPhotoFiles);
    }

    function syncPhotoInput(input) {
        if (!input || typeof DataTransfer === 'undefined') {
            return;
        }

        var transfer = new DataTransfer();
        selectedPhotoFiles.forEach(function (file) {
            transfer.items.add(file);
        });
        input.files = transfer.files;
    }

    function showPhotoPreview(files) {
        var preview = $('#note-photo-preview');
        var list = $('#note-photo-preview-list');

        if (!preview || !list) {
            return;
        }

        photoPreviewUrls.forEach(function (url) {
            URL.revokeObjectURL(url);
        });
        photoPreviewUrls = [];
        list.innerHTML = '';

        if (!files.length) {
            preview.hidden = true;
            return;
        }

        files.forEach(function (file, index) {
            var url = URL.createObjectURL(file);
            var item = document.createElement('figure');
            var image = document.createElement('img');
            var removeButton = document.createElement('button');

            photoPreviewUrls.push(url);
            item.className = 'photo-preview-item';
            item.draggable = true;
            item.dataset.index = String(index);

            image.src = url;
            image.alt = file.name || '업로드한 사진';

            removeButton.type = 'button';
            removeButton.className = 'photo-preview-remove';
            removeButton.dataset.action = 'remove-photo';
            removeButton.setAttribute('aria-label', '사진 제거');
            removeButton.textContent = '×';

            item.appendChild(image);
            item.appendChild(removeButton);
            list.appendChild(item);
        });

        preview.hidden = false;
    }

    function bindPhotoPreviewActions(input, list) {
        list.addEventListener('click', function (event) {
            var removeButton = event.target.closest('[data-action="remove-photo"]');
            if (!removeButton) {
                return;
            }

            var item = removeButton.closest('.photo-preview-item');
            removePhotoAt(Number(item.dataset.index), input);
        });

        list.addEventListener('dragstart', function (event) {
            var item = event.target.closest('.photo-preview-item');
            if (!item) {
                return;
            }

            draggedPhotoIndex = Number(item.dataset.index);
            item.classList.add('is-moving');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', item.dataset.index);
            }
        });

        list.addEventListener('dragover', function (event) {
            if (draggedPhotoIndex === null) {
                return;
            }
            event.preventDefault();
        });

        list.addEventListener('drop', function (event) {
            var item = event.target.closest('.photo-preview-item');
            event.preventDefault();
            if (!item || draggedPhotoIndex === null) {
                return;
            }

            movePhoto(draggedPhotoIndex, Number(item.dataset.index), input);
            draggedPhotoIndex = null;
        });

        list.addEventListener('dragend', function () {
            draggedPhotoIndex = null;
            clearPhotoMovingState(list);
        });

        list.addEventListener('pointerdown', function (event) {
            var item = event.target.closest('.photo-preview-item');
            if (!item || event.target.closest('[data-action="remove-photo"]')) {
                return;
            }

            longPressTimer = setTimeout(function () {
                draggedPhotoIndex = Number(item.dataset.index);
                item.classList.add('is-moving');
            }, 280);
        });

        list.addEventListener('pointermove', function (event) {
            if (draggedPhotoIndex === null) {
                return;
            }

            var target = document.elementFromPoint(event.clientX, event.clientY);
            var item = target ? target.closest('.photo-preview-item') : null;
            if (!item) {
                return;
            }

            var targetIndex = Number(item.dataset.index);
            if (targetIndex !== draggedPhotoIndex) {
                movePhoto(draggedPhotoIndex, targetIndex, input);
                draggedPhotoIndex = targetIndex;
            }
        });

        list.addEventListener('pointerup', function () {
            clearTimeout(longPressTimer);
            draggedPhotoIndex = null;
            clearPhotoMovingState(list);
        });

        list.addEventListener('pointerleave', function () {
            clearTimeout(longPressTimer);
        });
    }

    function removePhotoAt(index, input) {
        if (index < 0 || index >= selectedPhotoFiles.length) {
            return;
        }

        selectedPhotoFiles.splice(index, 1);
        syncPhotoInput(input);
        showPhotoPreview(selectedPhotoFiles);
    }

    function movePhoto(fromIndex, toIndex, input) {
        if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= selectedPhotoFiles.length || toIndex >= selectedPhotoFiles.length) {
            return;
        }

        var moved = selectedPhotoFiles.splice(fromIndex, 1)[0];
        selectedPhotoFiles.splice(toIndex, 0, moved);
        syncPhotoInput(input);
        showPhotoPreview(selectedPhotoFiles);
    }

    function clearPhotoMovingState(list) {
        list.querySelectorAll('.photo-preview-item').forEach(function (item) {
            item.classList.remove('is-moving');
        });
    }

    function bindStarRating() {
        var rating = $('#note-rating');
        var ratingLabel = $('#note-rating-label');
        var starRating = $('.star-rating');

        if (!rating || !ratingLabel || !starRating) {
            return;
        }

        starRating.addEventListener('click', function (event) {
            var button = event.target.closest('[data-rating]');
            if (!button) {
                return;
            }

            var value = Number(button.dataset.rating);
            setRatingValue(value, rating, ratingLabel, starRating);
        });
    }

    function setRatingValue(value, rating, ratingLabel, starRating) {
        if (Number.isNaN(value)) {
            return;
        }

        rating.value = value ? String(value) : '';
        ratingLabel.textContent = value ? value.toFixed(1).replace(/\.0$/, '') + '점' : '선택 안 함';
        starRating.querySelectorAll('[data-rating]').forEach(function (button) {
            button.setAttribute('aria-pressed', Number(button.dataset.rating) <= value ? 'true' : 'false');
        });
    }

    function openNoteModal() {
        var modal = $('#place-note-modal');
        if (!modal) {
            return;
        }

        modal.hidden = false;
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMap();
        bindSearch();
        bindMapActions();
        bindFileUpload();
        bindStarRating();
        bindNoteForm();
    });
})();
