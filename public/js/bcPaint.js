
$(document).ready(function(){
	console.log('bcpaint')
	$('body').on('click', '.bcPaint-palette-color', function(){
		$(this).parent().find('.selected').removeClass('selected');
		$(this).addClass('selected');
		$.fn.bcPaint.setColor($(this).css('background-color'));
	});

	$('body').on('click', '#bcPaint-reset', function(){
		$.fn.bcPaint.clearCanvas();
	});

	$('body').on('click', '#bcPaint-export', function(){
		$.fn.bcPaint.export();
	});
	$('body').on('click', '#toolbar_zoom_out', function(){
		let tmpcanvas = document.getElementById("bcPaintCanvas");
		let ctx = tmpcanvas.getContext("2d");
		
		let image = new Image();
		// $('#bcPaintCanvas').attr('height',$('#bcPaintCanvas').height()/2);
		// $('#bcPaintCanvas').attr('width',$('#bcPaintCanvas').width()/2);
		// ctx.drawImage( tmpcanvas, 0, 0, tmpcanvas.width/2, tmpcanvas.height/2 );
		// image.onload = function() {
		// 	ctx.drawImage(image, 0, 0, $('#bcPaintCanvas').width(), $('#bcPaintCanvas').height());
		// };
		// image.src = tmpdata;
		let imageObject=new Image();
		imageObject.onload=function(){

			ctx.clearRect(0,0,tmpcanvas.width,tmpcanvas.height);
			ctx.scale(0.5,0.5);
			ctx.drawImage(imageObject,0,0);
		
		}
		let tmpdata  = tmpcanvas.toDataURL('image/png');
		$('#bcPaintCanvas').attr('height',tmpcanvas.height/2);
		$('#bcPaintCanvas').attr('width',tmpcanvas.width/2);
		imageObject.src=tmpdata;

	});
	$('body').on('click', '#toolbar_zoom_in', function(){
		var tmpcanvas = document.getElementById("bcPaintCanvas");
		
		var imgData = tmpcanvas.toDataURL('image/png');
		
		let tmpdata  = tmpcanvas.toDataURL('image/png');
		$('#bcPaintCanvas').attr('height',tmpcanvas.height*2);
		$('#bcPaintCanvas').attr('width',tmpcanvas.width*2);
		imageObject.src=tmpdata;
	});

	
});


(function( $ ) {
	/**
	* Private variables
	**/
	var isDragged		= false,
		startPoint		= { x:0, y:0 },
		templates 		= {
							container 		: $('<div class="row" id="bcPaint-container"></div>'),
							zoom 			: $('<div class="col-12 form-group"><div class="btn-group"><button type="button" id="toolbar_zoom_out" title="Zoom Out" class="btn btn-secondary">-</button><button type="button" id="toolbar_zoom_in" title="Zoom In" class="btn btn-secondary">+</button><button type="button" id="toolbar_zoom_reset" title="Zoom Reset" class="btn btn-secondary">=</button></div></div>'),
							textBox			: $('<button type="button" class="btn btn-link">文字框</button>'),
							color 			: $('<div class="bcPaint-palette-color"></div>'),
							canvasContainer : $('<div class="col-12 overflow-auto" id="bcPaint-canvas-container"></div>'),
							canvasPane 		: $('<canvas id="bcPaintCanvas"  class="border border-dark rounded" style="z-index: 9999;"></canvas>'),
							palette 		: $('<div class="col-sm-12 cols-md-12 bg-light rounded pt-4 text-center" id="bcPaint-palette"><h6 class="bg-dark rounded p-3 mb-4 text-white font-weight-normal">Color Palette</h6></div>'),
							bottom 			: $('<div class="col-sm-12 col-md-12 text-center mt-3" id="bcPaint-bottom"></div>'),
							buttonReset 	: $('<button type="button" class="btn btn-secondary btn-sm mr-1" id="bcPaint-reset"><i class="fas fa-eraser"></i> 清除</button>'),
							buttonSave		: $('<button type="button" class="btn btn-primary btn-sm ml-1" id="bcPaint-export"><i class="fas fa-download"></i> 儲存</button>'),
							textBoxCanvas 	: $('<canvas class="border border-dark rounded" style="position: absolute; top: 0; z-index: 1;" id="canvasTextBox" hidden></canvas>'),
							textBoxCard 	: $('<div class="card-group d-flex flex-row flex-nowrap overflow-auto" id="cardTextBox"></div>')
						},
		paintCanvas,
		paintContext,
		textBoxCanvas,
		textBoxCanvases	= [],
		textBox;

	/**
	* Assembly and initialize plugin
	**/
	$.fn.bcPaint = function (options,defaultTextBoxCanvases = [],defaultTextBoxInput = [] ,defaultXArr = [] ,defaultYArr = [],defaultwidthArr=[],defaultheightArr=[]) {

		return this.each(function () {
			var rootElement 	= $(this),
				colorSet		= $.extend({}, $.fn.bcPaint.defaults, options),
				defaultColor	= (rootElement.val().length > 0) ? rootElement.val() : colorSet.defaultColor,
				container 		= templates.container.clone(),
				zoom 			= templates.zoom.clone(),
				// header 			= templates.header.clone(),
				textBoxPane		= templates.textBoxCanvas.clone(),
				textBoxCard		= templates.textBoxCard.clone(),
				palette 		= $('#bcPaint-palette'),
				canvasContainer = templates.canvasContainer.clone(),
				canvasPane 		= templates.canvasPane.clone(),
				bottom 			= templates.bottom.clone(),
				buttonReset 	= templates.buttonReset.clone(),
				buttonSave 		= templates.buttonSave.clone(),
				color;
			textBox			= templates.textBox.clone();

			// assembly pane
			rootElement.append(container);
			// container.append(zoom);
			// container.append(header);
			// container.append(palette);
			container.append(canvasContainer);
			container.append(bottom);
			$('#divpalette').append(palette)
			$('#divpalette').append(textBox);
			canvasContainer.append(canvasPane);
			canvasContainer.append(textBoxPane);
			$('#divFunction').append(buttonReset);
			$('#divFunction').append(buttonSave);
			$('#divtextBoxCard').append(textBoxCard);

			
			textBoxCanvases	= defaultTextBoxCanvases;
			$(textBoxCanvases).each(function(i){
				textBoxCanvas = $('#canvasTextBox')[0].getContext('2d');
				$('#cardTextBox').append(`
					<div class="card col-2" style="min-width:200px">
						<div class="card-body">
							<button type="button" class="close" aria-label="Close" data-id="${i}">
								<span aria-hidden="true">&times;</span>
							</button>
							<button type="button" class="card-title btn btn-link" name="buttonTextBox" onclick="showTextBox(${i})" data-id="${i}">${i+1}</h5>
							<input type="text" class="form-control" name="inputTextBox" data-width="${defaultwidthArr[i]}"  data-height="${defaultheightArr[i]}" value="${defaultTextBoxInput[i]}" data-x="${defaultXArr[i]}" data-y="${defaultYArr[i]}" required/>
							</button>
						</div>
					</div>
				`);
			})
			// assembly color palette
			$.each(colorSet.colors, function (i) {
        		color = templates.color.clone();
				color.css('background-color', $.fn.bcPaint.toHex(colorSet.colors[i]));
				palette.append(color);
    		});

			// set canvas pane width and height
			var bcCanvas = rootElement.find('canvas');
			var bcCanvasContainer = rootElement.find('#bcPaint-canvas-container');
			// bcCanvas.attr('width', bcCanvasContainer.width());
			// bcCanvas.attr('height', bcCanvasContainer.height());

			// get canvas pane context
			paintCanvas = document.getElementById('bcPaintCanvas');
			paintContext = paintCanvas.getContext('2d');

			// set color
			$.fn.bcPaint.setColor(defaultColor);

			// bind mouse actions
			paintCanvas.onmousedown = $.fn.bcPaint.onMouseDown;
			paintCanvas.onmouseup = $.fn.bcPaint.onMouseUp;
			paintCanvas.onmousemove = $.fn.bcPaint.onMouseMove;

			// bind touch actions
			paintCanvas.addEventListener('touchstart', function(e){
				$.fn.bcPaint.dispatchMouseEvent(e, 'mousedown');
			});
			paintCanvas.addEventListener('touchend', function(e){
  				$.fn.bcPaint.dispatchMouseEvent(e, 'mouseup');
			});
			paintCanvas.addEventListener('touchmove', function(e){
				$.fn.bcPaint.dispatchMouseEvent(e, 'mousemove');
			});
			// bind mouse actions
			$(textBox).on('click',function(e){
				$('#canvasTextBox').prop('hidden',false)
				$('#canvasTextBox')[0].height=($('#bcPaintCanvas').height());
				$('#canvasTextBox')[0].width=($('#bcPaintCanvas').width());
				$('#canvasTextBox')[0].onmousedown = $.fn.bcPaint.onMouseDownRect;
				$('#canvasTextBox')[0].onmouseup = $.fn.bcPaint.onMouseUpRect;
				$('#canvasTextBox')[0].onmousemove = $.fn.bcPaint.onMouseMoveRect;
				textBoxCanvas = $('#canvasTextBox')[0].getContext('2d');
				$.fn.bcPaint.setColorRect('#ff0000');
			});

			// Prevent scrolling on touch event
			document.body.addEventListener("touchstart", function (e) {
			  if (e.target == 'paintCanvas') {
			    e.preventDefault();
			  }
			}, false);
			document.body.addEventListener("touchend", function (e) {
			  if (e.target == 'paintCanvas') {
			    e.preventDefault();
			  }
			}, false);
			document.body.addEventListener("touchmove", function (e) {
			  if (e.target == 'paintCanvas') {
			    e.preventDefault();
			  }
			}, false);

			$('[name="formtextBoxCard"]').on('submit', function(e) {
				e.preventDefault();
			  console.log('in')
			  let imgData = paintCanvas.toDataURL('image/png');
					let canvasArr=[];
					$('[name="inputTextBox"]').each(function(index,value){
						// console.log(textBoxCanvases[index]);
						// console.log($(this).val());
						let tmpObj = new Object();
						tmpObj['canvas'] = textBoxCanvases[index];
						tmpObj['mark'] = $(this).val();
						tmpObj['x'] = $(this).data('x');
						tmpObj['y'] = $(this).data('y');
						tmpObj['width'] = $(this).data('width');
						tmpObj['height'] = $(this).data('height');
						canvasArr.push(tmpObj)
		
					});
					$.ajax({
						url: `/file/file_comment/canvas`,
						type: 'post',
						data: {
							file_id : id,
							module_id : 2,
							canvas : imgData,
					
						},
						success: function(response) {
							
						}
					});
					$.ajax({
						url: `/file/file_comment/textbox`,
						type: 'post',
						data: {
							file_id : id,
							canvas : canvasArr
						},
						success: function(response) {
							
						}
					});
			   
			});
		});
	}

	/**
	* Extend plugin
	**/
	$.extend(true, $.fn.bcPaint, {

		/**
		* Dispatch mouse event
		*/
		dispatchMouseEvent : function(e, mouseAction){
			var touch = e.touches[0];
			if(touch == undefined){
				touch = { clientX : 0, clientY : 0 };
			}
			var mouseEvent = new MouseEvent(mouseAction, {
				clientX: touch.clientX,
				clientY: touch.clientY
			});
			paintCanvas.dispatchEvent(mouseEvent);
		},

		/**
		* Remove pane
		*/
		clearCanvas : function(){
			paintCanvas.width = paintCanvas.width;
		},

		/**
		* On mouse down
		**/
		onMouseDown : function(e){
			isDragged = true;
			// get mouse x and y coordinates
			startPoint.x = e.offsetX;
			startPoint.y = e.offsetY;
			// begin context path
			paintContext.beginPath();
			paintContext.moveTo(startPoint.x, startPoint.y);
		},

		/**
		* On mouse up
		**/
		onMouseUp : function() {
		    isDragged = false;
		},

		/**
		* On mouse move
		**/
		onMouseMove : function(e){
			if(isDragged){
				paintContext.lineTo(e.offsetX, e.offsetY);
				paintContext.stroke();
			}
		},
		/**
		* On mouse down
		**/
		onMouseDownRect : function(e){
			isDragged = true;
			// get mouse x and y coordinates
			startPoint.x = e.offsetX;
			startPoint.y = e.offsetY;
		},

		/**
		* On mouse up
		**/
		onMouseUpRect : function() {
		    isDragged = false;
			textBoxCanvases.push($('#canvasTextBox')[0].toDataURL('image/png'));
			$('#cardTextBox').append(`
				<div class="card col-2" style="min-width:200px">
					<div class="card-body">
						<button type="button" class="close" aria-label="Close" data-id="${textBoxCanvases.length-1}">
							<span aria-hidden="true">&times;</span>
						</button>
						<button type="button" class="card-title btn btn-link" name="buttonTextBox" onclick="showTextBox(${textBoxCanvases.length-1})" data-id="${textBoxCanvases.length-1}" >${textBoxCanvases.length}</h5>
						<input type="text" class="form-control" data-x="${startPoint.x}" data-y="${startPoint.y}" name="inputTextBox"  data-width="${paintCanvas.width}"  data-height="${paintCanvas.height}" required/>
						</button>

					</div>
				</div>
			`);
		},

		/**
		* On mouse move
		**/
		onMouseMoveRect : function(e){
			if(isDragged){
				textBoxCanvas.clearRect(0, 0, paintCanvas.width, paintCanvas.height);
				textBoxCanvas.beginPath();
				textBoxCanvas.rect(startPoint.x, startPoint.y, e.offsetX-startPoint.x, e.offsetY-startPoint.y);
				textBoxCanvas.lineWidth   = 3;
				textBoxCanvas.stroke();
			}
		},

		/**
		* Set selected color
		**/
		setColorRect : function(color){
			textBoxCanvas.strokeStyle = $.fn.bcPaint.toHex(color);
		},

		/**
		* Set selected color
		**/
		setColor : function(color){
			paintContext.strokeStyle = $.fn.bcPaint.toHex(color);
			$('#canvasTextBox').prop('hidden',true)
		},

		/**
		*
		*/
		export : function(){
			$('#btnformtextBoxCard').click();
			
		},

		/**
		* Convert color to HEX value
		**/
		toHex : function(color) {
		    // check if color is standard hex value
		    if (color.match(/[0-9A-F]{6}|[0-9A-F]{3}$/i)) {
		        return (color.charAt(0) === "#") ? color : ("#" + color);
		    // check if color is RGB value -> convert to hex
		    } else if (color.match(/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/)) {
		        var c = ([parseInt(RegExp.$1, 10), parseInt(RegExp.$2, 10), parseInt(RegExp.$3, 10)]),
		            pad = function (str) {
		                if (str.length < 2) {
		                    for (var i = 0, len = 2 - str.length; i < len; i++) {
		                        str = '0' + str;
		                    }
		                }
		                return str;
		            };
		        if (c.length === 3) {
		            var r = pad(c[0].toString(16)),
		                g = pad(c[1].toString(16)),
		                b = pad(c[2].toString(16));
		            return '#' + r + g + b;
		        }
		    // else do nothing
		    } else {
		        return false;
		    }
		}

	});
	$(document).on('click','.close',function(){
		textBoxCanvas.clearRect(0, 0, $('#canvasTextBox')[0].width, $('#canvasTextBox')[0].height);
		let tmpArr = [];
		let tmpXArr = [];
		let tmpYArr = [];
		let tmpwidthArr = [];
		let tmpheightArr = [];
		textBoxCanvases.splice($(this).attr('data-id'), 1);
		$('[name="inputTextBox"]').each(function(i){
			console.log($(this).val())
			tmpArr.push($(this).val() ) 
			tmpXArr.push($(this).data('x'));
			tmpYArr.push($(this).data('y'));
			tmpwidthArr.push($(this).data('width'));
			tmpheightArr.push($(this).data('height'));

		});
		tmpArr.splice($(this).attr('data-id'), 1);
		tmpXArr.splice($(this).attr('data-id'), 1);
		tmpYArr.splice($(this).attr('data-id'), 1);
		tmpwidthArr.splice($(this).attr('data-id'), 1);
		tmpheightArr.splice($(this).attr('data-id'), 1);
		$(this).closest('.card').remove();

		
		$('#cardTextBox').empty();
		$(textBoxCanvases).each(function(i){
			$('#cardTextBox').append(`
				<div class="card col-2" style="min-width:200px">
					<div class="card-body">
						<button type="button" class="close" aria-label="Close" data-id="${i}">
							<span aria-hidden="true">&times;</span>
						</button>
						<button type="button" class="card-title btn btn-link" name="buttonTextBox" onclick="showTextBox(${i})" data-id="${i}" >${i+1}</h5>
						<input type="text" class="form-control" name="inputTextBox" data-x="${tmpXArr[i]}" data-y="${tmpYArr[i]}" data-width="${tmpwidthArr[i]}"  data-height="${tmpheightArr[i]}" value="${tmpArr[i]||''}" required/>
						</button>

					</div>
				</div>
			`);
		})
	})
	// $(document).on('click','[name="buttonTextBox"]',function(){
	// 	$(textBox).click()
	// 	let image = new Image();
	// 	let tmpX = $(this).find('[name="inputTextBox"]').data('x')
	// 	let tmpY = $(this).find('[name="inputTextBox"]').data('y')
	// 	let tmptext = $(this).find('[name="inputTextBox"]').val();
	// 	let tmpwidth = $(this).find('[name="inputTextBox"]').data('width');
	// 	let tmpheight = $(this).find('[name="inputTextBox"]').data('height');
	// 	let ratio =  $('#canvasTextBox')[0].width/tmpwidth
	// 	// $('#bcPaintCanvas').height()
	// 	image.onload = function() {
	// 		textBoxCanvas.clearRect(0, 0, $('#canvasTextBox')[0].width, $('#canvasTextBox')[0].height);
	// 		textBoxCanvas.drawImage(image, 0, 0, $('#canvasTextBox')[0].width, $('#canvasTextBox')[0].height);
			
	// 		textBoxCanvas.font = "15px Arial";

	// 		var textwidth = textBoxCanvas.measureText(tmptext).width; 
	// 		var textheight = textBoxCanvas.measureText(tmptext).height; 
	// 		textBoxCanvas.fillStyle = '#f50';
	// 		textBoxCanvas.fillRect(tmpX*ratio, tmpY*ratio-parseInt("Arial", 15), textwidth, parseInt("Arial", 15));
	// 		textBoxCanvas.fillStyle = '#000';

	// 		textBoxCanvas.fillText(tmptext, tmpX*ratio, tmpY*ratio);	


	// 	};
	// 	image.src = textBoxCanvases[$(this).attr('data-id')]

		
	// })
	/**
	* Default color set
	**/
	$.fn.bcPaint.defaults = {
        // default color
        defaultColor : '000000',

        // default color set
        colors : [
					'000000', '444444', '999999', 'DDDDDD', '#e83e8c', '#dc3545',
					'#fd7e14', '#ffc107', '#28a745', '#20c997', '#6f42c1', '#007bff'
        ],

        // extend default set
        addColors : [],
    };

})(jQuery);
