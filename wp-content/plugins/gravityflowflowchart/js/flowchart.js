(function (GravityFlowFlowchart, $) {

	$(document).ready(function () {

		var strings = gravityflow_flowchart_js_strings;
		var steps;
		var paperArgs = gravityflow_flowchart_js_strings.vars.paper;
		var graphJSON = gravityflow_flowchart_js_strings.vars.graphJSON;
		var formId = gravityflow_flowchart_js_strings.vars.formId;

		var context = gravityflow_flowchart_js_strings.vars.context;

		var graph = new joint.dia.Graph;

		var graphScale = 1;

		var printAction = '';

		if ( context == 'display' ) {
			printAction = ajaxurl + '?action=gravityflowflowchart_print_flowchart';
			$('#gform-settings').before('<div id="flowchart-icon"><a href="#" title="' + strings.flowchart + '"><i class="fa fa-sitemap" aria-hidden="true"></i></a></div>' +
				'<div id="flowchart-toolbar" style="display:none;">' +
					'<form id="print-flowchart-form" action="' + printAction + '" method="POST" target="_blank">' +
						'<i id="flowchart-spinner" class="fa fa-spinner fa-pulse fa-fw"></i>' +
						'<span id="zoom-level">100%</span>' +
						'<a title="' + strings.zoomIn + '" id="zoom-in" href="#"><i class="fa fa-search-plus" aria-hidden="true"></i></a>' +
						'<a title="' + strings.zoomOut + '" id="zoom-out" href="#"><i class="fa fa-search-minus" aria-hidden="true"></i></a>' +
						'<input id="flowchart-nonce" type="hidden" name="_wpnonce" value="" />' +
						'<input id="flowchart-json" type="hidden" name="flowchart-json" value="" />' +
						'<input id="graph-scale" type="hidden" name="graph-scale" value="" />' +
						'<a title="' + strings.print + '" id="print-flowchart" href="#" onclick="GravityFlowFlowchart.print(event);"><i class="fa fa-print" aria-hidden="true" ></i></a>' +
						'<a title="' + strings.stepList + '" id="step-list" href="#"><i class="fa fa-list" aria-hidden="true"></i></a>' +
					'</form>' +
				'</div>' +
				'<div>' +
					'<div id="flowchart-container"></div>' +
				'</div>'
			);
		} else {
			displayChart();
		}

		$('#flowchart-icon').click( function( e ){
			$('#gform-settings').hide();


			$(this).hide();
			$('#flowchart-toolbar').show();
			startSpinner();
			$('#flowchart-container').show();
			displayChart();
			e.preventDefault();
		})

		function startSpinner() {
			$('#flowchart-spinner').show();
		}

		function stopSpinner() {
			$('#flowchart-spinner').hide();
		}

		var paper;

		function paperScale(sx, sy) {
			paper.scale(sx, sy);
			$('#zoom-level').text(Math.round( graphScale * 100 ) + '%' );
		}

		function zoomOut() {
			graphScale -= 0.1;
			paperScale(graphScale, graphScale);
		}

		function zoomIn() {
			graphScale += 0.1;
			paperScale(graphScale, graphScale);
		}


		function displayChart() {
			paper = new joint.dia.Paper({
				el: $('#flowchart-container'),
				width: '100%',
				height: 800,
				model: graph,
				gridSize: paperArgs.gridSize,
				perpendicularLinks: true,
				drawGrid: paperArgs.drawGrid
			});

			graph.on('batch:start', function( args ) {
				if ( args.batchName == 'layout' ) {
					startSpinner();
				}
			});

			graph.on('batch:stop', function( args ) {
				if ( args.batchName == 'layout' ) {
					stopSpinner();
				}
			});

			if ( graphJSON ) {
				graphScale = gravityflow_flowchart_js_strings.vars.graphScale;
				paperScale(graphScale, graphScale);
				graph.fromJSON(graphJSON)
				resizePaper();
			} else {
				loadStepsAndGenerate();
			}

			function loadStepsAndGenerate(){
				$.get( ajaxurl + '?action=gravityflowflowchart_get_step_data&_wpnonce=' + strings.vars.nonce + '&id=' + formId, function( data ) {
					steps = JSON.parse(data);
					generateFlowchart();
				});
			}

			function generateFlowchart() {

				var cells = [];

				graph.resetCells( cells );

				var startRect = new joint.shapes.basic.Rect({
					position: { x: 100, y: 50 },
					size: { width: 200, height: 50 },
					attrs: { rect: { fill: 'green', rx: 15, ry: 30, }, text: { text: 'start', fill: 'white' } },
					ports: {
						groups: {
							'out': {
								position: {
									name: 'bottom',
									args: {},
								},
							}
						},
					}
				});

				var startPort = {
					id: 'start',
					group: 'out',
					args: {},
					label: {
						position: {
							name: 'bottom',
							args: {}
						},
					},
					attrs: { circle: { fill: 'white' } },
					markup: '<circle r="5" stroke="#000000" fill="white"/>'
				};

				startRect.addPort(startPort);

				cells.push( startRect );

				var rects = [], stepInfo = [], inPort, i, len, step, text, rect, portColor, x, port, textIcon, imageIcon, scheduleIcon;

				for (i = 0, len = steps.length; i < len; i++) {
					step = steps[i];
					stepInfo[step.id] = step;
					text = joint.util.breakText(step.name,{width:180});

					if ( typeof step.icon == 'string' ) {
						textIcon = null;
						imageIcon = { 'xlink:href': step.icon, 'ref-x': 10, 'ref-y': 75, ref: 'rect', width: 16, height: 16 };
					} else {
						// Convert to unicode char
						step.icon.text = String.fromCharCode(parseInt(step.icon.text,16));
						textIcon = { text: step.icon.text, fill: step.icon.color, 'font-size': 16, 'ref-x': 10, 'ref-y': 75, ref: 'rect', width: 16, height: 16, 'font-family': 'FontAwesome' }
						imageIcon = { 'xlink:href': null, 'ref-x': 10, 'ref-y': 75, ref: 'rect', width: 16, height: 16 };
					}

					scheduleIcon = step.scheduled ? { text: '\uf017', fill: 'gray', 'font-size': 16, 'ref-x': 180, 'ref-y': 10, ref: 'rect', width: 16, height: 16, 'font-family': 'FontAwesome' } : null;

					rect = new joint.shapes.basic.Generic({
						markup: '<g class="rotatable"><g class="scalable"><rect/></g><a><text class="step-schedule-icon"/><text class="step-name"/><image class="step-image-icon"/><text class="step-text-icon"/><text class="step-type"/></a></g>',
						position: { x: 100, y: 50 + ( i * 50 ) },
						size: { width: 200, height: 100 },
						attrs: {
							rect: { fill: 'white', stroke: 'black', width: 200, height: 100,rx: 10,
								ry: 10, },
							'.step-schedule-icon' : scheduleIcon,
							'.step-name': { text: text, fill: 'gray', 'font-size': 12, 'ref-x': .5, 'ref-y': 20, ref: 'rect', 'x-alignment': 'middle', 'font-family': 'sans-serif'  },
							'.step-image-icon': imageIcon,
							'.step-text-icon' : textIcon,
							'.step-type': { text: step.label, fill: 'gray', 'font-size': 12, 'ref-x': 20, 'ref-y':2, ref: 'image', 'font-family': 'sans-serif'  },
							a: {
								xlinkHref: step.settings_url,
								cursor: 'pointer'
							},
						},
						ports: {
							groups: {
								'in': {
									position: {
										name: 'top',
										args: {

										},
									},
									markup: '<circle r="5" stroke="#000000" fill="white"/>'
								},
								'out': {
									position: {
										name: 'bottom',
										args: {},
									}
								}
							},
						}
					});

					// Single port definition
					inPort = {
						id: 'in',
						group: 'in',
						args: {},
						label: {
							position: {
								name: 'top',
								args: {}
							},
						},
						markup: '<circle r="5" stroke="#000000" fill="white"/>'
					};
					rect.addPort(inPort);

					for ( x = 0; x < step.targets.length; x++) {
						switch ( status = step.targets[x].status ) {
							case 'approved' :
								portColor = 'green';
								break;
							case 'rejected' :
								portColor = 'red';
								break;
							case 'reverted' :
								portColor = 'blue';
								break;
							case 'expired' :
								portColor = 'purple';
								break;
							case 'skipped' :
								portColor = 'silver';
								break;
							default :
								portColor = 'white';
						}
						// Single port definition
						port = {
							id: step.targets[x].status,
							group: 'out',
							args: {},
							label: {
								position: {
									name: 'bottom',
									args: {}
								},
							},
							attrs: { circle: { fill: portColor } },
							markup: '<circle r="5" stroke="#000000" fill="' + portColor + '"/>'
						};
						rect.addPort(port);

					}

					cells.push( rect );
					rects[ 'id' + step.id ] = rect;

				}

				var completeRect = new joint.shapes.basic.Rect({
					position: { x: 100, y: 50 + ( i * 50 ) },
					size: { width: 200, height: 50 },
					attrs: { rect: { fill: 'red', rx: 15, ry: 30, }, text: { text: 'complete', fill: 'white' } }
				});

				cells.push( completeRect );

				var link, n, target, status, labelColor, linkLabel, dashed, targetRect, conditionalLogiclink;

				for (i = 0; i < len; i++) {
					step = steps[i];
					rect = rects[  'id' + steps[i].id ];

					if ( i == 0 ) {
						link = getLink( { id: startRect.id, port: 'start' }, { id: rect.id, port: 'in' });
						cells.push( link );
					}

					for (n = 0; n < step.targets.length; n++) {

						target = step.targets[n].step_id;
						status = step.targets[n].status;

						switch ( status = step.targets[n].status ) {
							case 'approved' :
								labelColor = 'green';
								break;
							case 'rejected' :
								labelColor = 'red';
								break;
							case 'reverted' :
								labelColor = 'blue';
								break;
							case 'expired' :
								labelColor = 'purple';
								break;
							case 'skipped' :
								labelColor = 'silver';
								break;
							default :
								labelColor = 'gray';
						}

						linkLabel = step.targets[n].status;

						if ( linkLabel == 'complete' ) {
							linkLabel = '';
						}

						dashed = status == 'skipped';

						if ( target == 'complete' ) {
							link = getLink( { id: rect.id, port: status }, { id: completeRect.id }, linkLabel, labelColor, dashed );

							cells.push( link );
						} else {

							targetRect = rects[  'id' + target ];

							conditionalLogiclink = getLink({ id: rect.id, port: status},  { id: targetRect.id, port: 'in' }, linkLabel, labelColor, dashed )

							cells.push( conditionalLogiclink );
						}
					}
				}
				paperScale(graphScale, graphScale);
				graph.addCells( cells );
				autoLayout();
				resizePaper();
			}

			function autoLayout() {
				joint.layout.DirectedGraph.layout(graph, {
					nodeSep: 75,
					edgeSep: 50,
					rankSep: 70,
					marginX: 50,
					marginY: 50,
					rankDir: "TD",
					// Possible values: network-simplex, tight-tree or longest-path
					ranker: "longest-path"
				});
				graph.resetCells( graph.getCells() );
			}

			paper.on('blank:pointerdown',
				function(event, x, y) {
					var scale = V(paper.viewport).scale();
					dragStartPosition = { x: x * scale.sx, y: y * scale.sy};
				}
			);

			paper.on('cell:pointerup blank:pointerup', function(cellView, x, y) {
				delete dragStartPosition;
			});

			$("#flowchart-container")
				.mousedown(function(){
					$("#flowchart-container svg").css({cursor:'-webkit-grabbing', cursor:'-webkit-grabbing'})
				})
				.mouseup(function(){
					$("#flowchart-container svg").css({cursor:'-webkit-grab', cursor:'-webkit-grab'})
				})
				.mousemove(function(event) {
					if ( typeof dragStartPosition != 'undefined' ) {
						paper.translate(
							event.offsetX - dragStartPosition.x,
							event.offsetY - dragStartPosition.y
						);
					}

				});

			function resizePaper(){
				paper.fitToContent( '100%', null, 100 );
			}

			function getLink( source, target, label, labelColor, dashed, startDirection, endDirection ) {

				if ( ! startDirection ) {
					startDirection = 'bottom';
				}

				if ( ! endDirection ) {
					endDirection = 'top';
				}
				var linkArgs = {
					source: source,
					target: target,
					router: {
						name: 'manhattan',
						args: {
							startDirections: [startDirection],
							endDirections: [endDirection],
							maximumLoops: 1000,
							maxAllowedDirectionChange: 9999,
							perpendicular: true,
							excludeEnds:false,
							step: 10,
						}
					},
					connector: { name: 'rounded' },
					attrs: {
						'.connection': {
							stroke: '#CCCCCC',
							'stroke-width': 2,
						},
						'.marker-target': {
							fill: '#333333',
							d: 'M 10 0 L 0 5 L 10 10 z'
						},
						//'.marker-vertices': { display : 'none' },
						'.marker-arrowheads': { display: 'none' },
						'.link-tools': { display : 'none' },
					}
				};

				if ( dashed ) {
					linkArgs.attrs['.connection']['stroke-dasharray'] = '6,5';
				}

				if ( label ) {
					linkArgs.labels = [
						{ position: 0.5, attrs: { text: { text: label, fill: labelColor, 'font-family': 'sans-serif' } }}
					]
				}

				return new joint.dia.Link( linkArgs );
			}
		}

		GravityFlowFlowchart.print = function(e){
			e.preventDefault();
			var json = JSON.stringify(graph);
			$('#flowchart-json').val( json );
			$('#flowchart-nonce').val( strings.vars.nonce );
			$('#graph-scale').val( graphScale );

			$('#print-flowchart-form').submit();
		}

		$('#zoom-in').click( function( e ){
			zoomIn();
			e.preventDefault();
		});

		$('#zoom-out').click( function( e ){
			zoomOut();
			e.preventDefault();
		});

		$('#step-list').click( function( e ){
			$('#flowchart-container').hide();
			$('#gform-settings').show();
			$('#flowchart-toolbar').hide();
			$('#flowchart-icon').show();
			e.preventDefault();
		});
	});

}(window.GravityFlowFlowchart = window.GravityFlowFlowchart || {}, jQuery));
