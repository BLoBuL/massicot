(function (window, document) {
	'use strict';

	function nombre(value, fallback) {
		value = Number(value);
		return Number.isFinite(value) ? value : fallback;
	}

	function limiter(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function init(form, options) {
		options = options || {};
		var page = form.closest('.massicoter_image') || document;
		var viewport = page.querySelector('.image-massicot');
		var canvas = viewport.querySelector('.massicot-canevas');
		var image = canvas.querySelector('img');
		var selection = canvas.querySelector('.massicot-selection');
		var zoom = form.querySelector('.massicot-zoom');
		var zoomValue = form.querySelector('.massicot-zoom-value');
		var dimensions = form.querySelector('.dimensions');
		var reset = form.querySelector('.bouton_reset');
		var fields = {};
		['x1', 'x2', 'y1', 'y2', 'zoom'].forEach(function (name) {
			fields[name] = form.querySelector('[name="' + name + '"]');
		});
		var forced = options.forcer_dimensions || null;
		var ratio = forced ? nombre(forced.largeur, 1) / nombre(forced.hauteur, 1) : null;
		var sourceWidth = 0;
		var sourceHeight = 0;
		var state;
		var interaction;

		function readState() {
			return {
				x1: nombre(fields.x1.value, 0),
				x2: nombre(fields.x2.value, sourceWidth),
				y1: nombre(fields.y1.value, 0),
				y2: nombre(fields.y2.value, sourceHeight),
				zoom: nombre(fields.zoom.value, nombre(options.zoom, 1))
			};
		}

		function normalize(next) {
			var maxX = Math.round(sourceWidth * next.zoom);
			var maxY = Math.round(sourceHeight * next.zoom);
			next.x1 = limiter(Math.round(next.x1), 0, Math.max(0, maxX - 1));
			next.y1 = limiter(Math.round(next.y1), 0, Math.max(0, maxY - 1));
			next.x2 = limiter(Math.round(next.x2), next.x1 + 1, maxX);
			next.y2 = limiter(Math.round(next.y2), next.y1 + 1, maxY);
			return next;
		}

		function writeState(next) {
			state = normalize(next);
			Object.keys(fields).forEach(function (name) {
				fields[name].value = state[name];
			});
			zoom.value = state.zoom;
			zoomValue.value = state.zoom.toFixed(2).replace(/0+$/, '').replace(/\.$/, '') + '×';
			dimensions.textContent = (state.x2 - state.x1) + ' × ' + (state.y2 - state.y1);
			canvas.style.width = Math.round(sourceWidth * state.zoom) + 'px';
			canvas.style.height = Math.round(sourceHeight * state.zoom) + 'px';
			selection.style.left = state.x1 + 'px';
			selection.style.top = state.y1 + 'px';
			selection.style.width = (state.x2 - state.x1) + 'px';
			selection.style.height = (state.y2 - state.y1) + 'px';
		}

		function resizeFrom(handle, start, dx, dy) {
			var next = Object.assign({}, start);
			if (handle.indexOf('w') !== -1) { next.x1 += dx; }
			if (handle.indexOf('e') !== -1) { next.x2 += dx; }
			if (handle.indexOf('n') !== -1) { next.y1 += dy; }
			if (handle.indexOf('s') !== -1) { next.y2 += dy; }
			if (ratio) {
				var width = next.x2 - next.x1;
				var height = width / ratio;
				if (handle.indexOf('n') !== -1) { next.y1 = next.y2 - height; }
				else { next.y2 = next.y1 + height; }
			}
			return normalize(next);
		}

		function moveFrom(start, dx, dy) {
			var width = start.x2 - start.x1;
			var height = start.y2 - start.y1;
			var maxX = sourceWidth * start.zoom;
			var maxY = sourceHeight * start.zoom;
			var x1 = limiter(start.x1 + dx, 0, maxX - width);
			var y1 = limiter(start.y1 + dy, 0, maxY - height);
			return Object.assign({}, start, {x1: x1, x2: x1 + width, y1: y1, y2: y1 + height});
		}

		function pointerDown(event) {
			if (event.button !== undefined && event.button !== 0) { return; }
			interaction = {
				x: event.clientX,
				y: event.clientY,
				state: Object.assign({}, state),
				handle: event.target.dataset.handle || ''
			};
			selection.setPointerCapture(event.pointerId);
			event.preventDefault();
		}

		function pointerMove(event) {
			if (!interaction) { return; }
			var dx = event.clientX - interaction.x;
			var dy = event.clientY - interaction.y;
			writeState(interaction.handle
				? resizeFrom(interaction.handle, interaction.state, dx, dy)
				: moveFrom(interaction.state, dx, dy));
		}

		function pointerUp() { interaction = null; }

		function keyboard(event) {
			if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) { return; }
			var step = event.shiftKey ? 10 : 1;
			var dx = event.key === 'ArrowLeft' ? -step : (event.key === 'ArrowRight' ? step : 0);
			var dy = event.key === 'ArrowUp' ? -step : (event.key === 'ArrowDown' ? step : 0);
			var handle = event.target.dataset.handle || '';
			writeState(handle ? resizeFrom(handle, state, dx, dy) : moveFrom(state, dx, dy));
			event.preventDefault();
		}

		function zoomChanged() {
			var oldZoom = state.zoom;
			var newZoom = nombre(zoom.value, oldZoom);
			var scale = newZoom / oldZoom;
			writeState({
				x1: state.x1 * scale, x2: state.x2 * scale,
				y1: state.y1 * scale, y2: state.y2 * scale,
				zoom: newZoom
			});
		}

		function resetAll() {
			writeState({x1: 0, y1: 0, x2: sourceWidth, y2: sourceHeight, zoom: 1});
		}

		function ready() {
			sourceWidth = image.naturalWidth || nombre(image.getAttribute('width'), 0);
			sourceHeight = image.naturalHeight || nombre(image.getAttribute('height'), 0);
			if (!sourceWidth || !sourceHeight) { return; }
			state = readState();
			if (!fields.x2.value || !fields.y2.value) { resetAll(); }
			else { writeState(state); }
			selection.addEventListener('pointerdown', pointerDown);
			selection.addEventListener('pointermove', pointerMove);
			selection.addEventListener('pointerup', pointerUp);
			selection.addEventListener('pointercancel', pointerUp);
			selection.addEventListener('keydown', keyboard);
			zoom.addEventListener('input', zoomChanged);
			reset.addEventListener('click', resetAll);
			form.classList.add('massicot-ready');
		}

		if (image.complete) { ready(); }
		else { image.addEventListener('load', ready, {once: true}); }

		return {getState: function () { return Object.assign({}, state); }, reset: resetAll};
	}

	window.MassicotCropper = {init: init};
}(window, document));
