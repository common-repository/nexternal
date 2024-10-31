(function() {
	tinymce.PluginManager.add('nexternal', function( editor, url ) {
		var sh_tag = 'nexternal';

		// get plugin base url
		url = url.replace(/\/js$/,''); // remove js subdir to step back up
		url = url.replace('http://','//'); // set to whatever protocol site is running on


		//helper functions
		function getAttr(s, n) {
			n = new RegExp(n + ' ?= ?\"([^\"]+)\"', 'g').exec(s);
			return n ?  window.decodeURIComponent(n[1]) : '';
		}

		function html( cls, data) {
			var placeholder = url + '/img/nexternal_logo.png';
			var data2 = window.encodeURIComponent( data );
			//alert(data);
			var img = '<img style="border-radius:5px;background:#efefef;border:1px solid #999999;" src="' + placeholder + '" class="mceItem ' + cls + '" ' + 'data-sh-attr="' + data2 + '" data-mce-resize="false" data-mce-placeholder="1" >';
			//var details = '<br/><span>Products: '+getAttr(data,'productIDs')+'</span>';
			//return '<div id="ph_'+getAttr(data,'id')+'" class="next-disp">'+img+details+'</div>';
            return img;
		}

		function replaceShortcodes( content ) {
			return content.replace( /\[nexternal([^\]]*)\]/g, function( all,attr) {
				return html( 'nexternal_panel', attr);
			});
		}

		function restoreShortcodes( content ) {
			return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>).+?(?:<?\/p>)*/g, function( match, image ) {
				var data = getAttr( image, 'data-sh-attr' );

				if ( data ) {
					return '<p>[' + sh_tag + data + ']</p>';
				}
				return match;
			});
		}

		//add popup
		editor.addCommand('nexternal_popup', function(ui, v) {
			//we're going to bypass this and pull window.php
			var urladd = '';
			for(var i in v) {
			  if(v.hasOwnProperty(i)) {
				  if(i != 'header' && i != 'footer' && i != 'type' && i != 'content') {
				  	urladd += '&'+i+'='+encodeURIComponent(v[i]);
				  }
			  }
			}
			if(urladd != '') {
				urladd = '?mode=edit'+urladd;
			    //alert(urladd);
		    }
			editor.windowManager.open( {
					file : url + '/tinymce/window.php'+urladd,
					width: Math.min(jQuery( window ).width() * 0.8,968),
					height: (jQuery( window ).height() - 36 - 50) * 0.8,
					inline : 1
			}, {
					plugin_url : url // Plugin absolute URL
			});

	    });

		//add button
		editor.addButton('nexternal', {
			icon: 'nexternal',
			image: url + '/tinymce/nexternalPlugin.gif',
			tooltip: 'Nexternal Products',
			onclick: function() {
				editor.execCommand('nexternal_popup','',{
					header : '',
					footer : '',
					type   : 'default',
					content: ''
				});
			}
		});

		//replace from shortcode to an image placeholder
		editor.on('BeforeSetcontent', function(event){
			event.content = replaceShortcodes( event.content );
		});

		//replace from image placeholder to shortcode
		editor.on('GetContent', function(event){
			event.content = restoreShortcodes(event.content);
		});

		//open popup on placeholder double click
		editor.on('DblClick',function(e) {
			if ( e.target.nodeName == 'IMG' && e.target.className.indexOf('nexternal_panel') > -1 ) {
				var title = e.target.attributes['data-sh-attr'].value;
				e.target = e.target.parentElement;
				title = window.decodeURIComponent(title);
				title = title.replace(/^ +/,'');
				title = title.replace(/[ \"]$/,'');
				var tarr = title.split('" ');
				var odata = {};
				for(var i in tarr) {
				  var varr = tarr[i].split(/ *= *\"/);
				  if(varr[0] && varr[1]) {
				    odata[varr[0]] = varr[1];
				  }
				}
				console.log(title);
				editor.execCommand('nexternal_popup','',odata);
			}
		});
	});
})();