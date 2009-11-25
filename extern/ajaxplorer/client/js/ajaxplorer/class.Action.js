/**
 * @package info.ajaxplorer.plugins
 * 
 * Copyright 2007-2009 Charles du Jeu
 * This file is part of AjaXplorer.
 * The latest code can be found at http://www.ajaxplorer.info/
 * 
 * This program is published under the LGPL Gnu Lesser General Public License.
 * You should have received a copy of the license along with AjaXplorer.
 * 
 * The main conditions are as follow : 
 * You must conspicuously and appropriately publish on each copy distributed 
 * an appropriate copyright notice and disclaimer of warranty and keep intact 
 * all the notices that refer to this License and to the absence of any warranty; 
 * and give any other recipients of the Program a copy of the GNU Lesser General 
 * Public License along with the Program. 
 * 
 * If you modify your copy or copies of the library or any portion of it, you may 
 * distribute the resulting library provided you do so under the GNU Lesser 
 * General Public License. However, programs that link to the library may be 
 * licensed under terms of your choice, so long as the library itself can be changed. 
 * Any translation of the GNU Lesser General Public License must be accompanied by the 
 * GNU Lesser General Public License.
 * 
 * If you copy or distribute the program, you must accompany it with the complete 
 * corresponding machine-readable source code or with a written offer, valid for at 
 * least three years, to furnish the complete corresponding machine-readable source code. 
 * 
 * Any of the above conditions can be waived if you get permission from the copyright holder.
 * AjaXplorer is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * Description : A "Command" object, encapsulating its callbacks, display attributes, etc.
 */
Action = Class.create({

	__DEFAULT_ICON_PATH : "/images/crystal/actions/ICON_SIZE",
	
	initialize:function(){
		this.options = Object.extend({
			name:'',
			src:'',
			text:'',
			title:'',
			hasAccessKey:false,
			accessKey:'',
			callbackCode:'',
			callback:Prototype.emptyFunction,
			displayAction:false,
			prepareModal:false, 
			formId:undefined, 
			formCode:undefined
			}, arguments[0] || { });
		this.context = Object.extend({
			selection:true,
			dir:false,
			allowedMimes:$A([]),
			ulteoMimes:$A([]),
			root:true,
			inZip:true,
			recycle:false,
			behaviour:'hidden',
			actionBar:false,
			actionBarGroup:'default',
			contextMenu:false,
			infoPanel:false			
			}, arguments[1] || { });
			
		this.selectionContext = Object.extend({			
			dir:false,
			file:true,
			recycle:false,
			behaviour:'disabled',
			allowedMimes:$A([]),			
			unique:true,
			multipleOnly:false
			}, arguments[2] || { });
		this.rightsContext = Object.extend({			
			noUser:true,
			userLogged:true,
			guestLogged:false,
			read:true,
			write:false,
			adminOnly:false
			}, arguments[3] || { });
		
		this.elements = new Array();
		this.contextHidden = false;
		this.deny = false;
		this.isUlteoApplication = false;
	}, 
	
	apply: function(){
		if(this.deny) return;
		if(this.options.prepareModal){
			modal.prepareHeader(
				this.options.title, 
				resolveImageSource(this.options.src,this.__DEFAULT_ICON_PATH, 16)
			);
		}
		window.actionArguments = $A([]);
		if(arguments[0]) window.actionArguments = $A(arguments[0]);
		if(this.options.callbackCode) this.options.callbackCode.evalScripts();
		window.actionArguments = null;
	},
	
	fireContextChange: function(){
		if(arguments.length < 5) return;
		var usersEnabled = arguments[0];
		var crtUser = arguments[1];
		var crtIsRecycle = arguments[2];
		var crtDisplayMode = arguments[3];
		var crtInZip = arguments[4];
		var crtIsRoot = arguments[5];
		var crtAjxpMime = arguments[6] || '';
		if(this.options.listeners && this.options.listeners["contextChange"]){
			this.options.listeners["contextChange"].evalScripts();
		}		
		var rightsContext = this.rightsContext;
		if(!rightsContext.noUser && !usersEnabled){
			return this.hideForContext();				
		}
		if((rightsContext.userLogged == 'only' && crtUser == null) ||
			(rightsContext.guestLogged && rightsContext.guestLogged=='hidden' & crtUser!=null && crtUser.id=='guest')){
			return this.hideForContext();
		}
		if(rightsContext.userLogged == 'hidden' && crtUser != null && !(crtUser.id=='guest' && rightsContext.guestLogged && rightsContext.guestLogged=='show') ){
			return this.hideForContext();
		}
		if(rightsContext.adminOnly && (crtUser == null || !crtUser.isAdmin)){
			return this.hideForContext();
		}
		if(rightsContext.read && crtUser != null && !crtUser.canRead()){
			return this.hideForContext();
		}
		if(rightsContext.write && crtUser != null && !crtUser.canWrite()){
			return this.hideForContext();
		}
		if(this.context.allowedMimes.length){
			if(!this.context.allowedMimes.indexOf(crtAjxpMime)==-1){
				return this.hideForContext();
			}
		}
		if(this.context.recycle){
			if(this.context.recycle == 'only' && !crtIsRecycle){
				return this.hideForContext();				
			}
			if(this.context.recycle == 'hidden' && crtIsRecycle){
				return this.hideForContext();
			}
		}
		if(!this.context.inZip && crtInZip){
			return this.hideForContext();
		}
		if(!this.context.root && crtIsRoot){
			return this.hideForContext();
		}
		if(this.options.displayAction && this.options.displayAction == crtDisplayMode){
			return this.hideForContext();
		}
		this.showForContext();				
		
	},
		
	fireSelectionChange: function(){
		if(this.options.listeners && this.options.listeners["selectionChange"]){
			this.options.listeners["selectionChange"].evalScripts();
		}
		if(arguments.length < 1 
			|| this.contextHidden 
			|| !this.context.selection) {	
			return;
		}
		var userSelection = arguments[0];		
		var bSelection = false;
		if(userSelection != null) 
		{			
			bSelection = !userSelection.isEmpty();
			var bUnique = userSelection.isUnique();
			var bFile = userSelection.hasFile();
			var bDir = userSelection.hasDir();
			var bRecycle = userSelection.isRecycle();
		}
		var selectionContext = this.selectionContext;
		if(selectionContext.allowedMimes.size()){
			if(selectionContext.behaviour == 'hidden') this.hide();
			else this.disable();
		}
		if(selectionContext.unique && !bUnique){
			return this.disable();
		}
		if((selectionContext.file || selectionContext.dir) && !bFile && !bDir){
			return this.disable();
		}
		if((selectionContext.dir && !selectionContext.file && bFile) 
			|| (!selectionContext.dir && selectionContext.file && bDir)){
			return this.disable();
		}
		if(!selectionContext.recycle && bRecycle){
			return this.disable();
		}
		if((selectionContext.allowedMimes.size() && userSelection && !userSelection.hasMime(selectionContext.allowedMimes)) 
			&& !(selectionContext.dir && bDir)){
			if(selectionContext.behaviour == 'hidden') return this.hide();
			else return this.disable();
		}
		if (this.isUlteoApplication && userSelection.hasFile()) {
			if (! this.canOpenUlteoMimeType(userSelection.getUniqueFileName())) {
				this.hide();
				return this.disable();
			}
		}
		this.show();
		this.enable();
		
	},

	canOpenUlteoMimeType:function(path_){
		var mime = getMimeType(path_);

		for (var i=0; i<this.context.ulteoMimes.length; i++) {
			if (this.context.ulteoMimes[i] == mime)
				return true;
			}

		return false;
	},
		
	createFromXML:function(xmlNode){
		this.options.name = xmlNode.getAttribute('name');
		for(var i=0; i<xmlNode.childNodes.length;i++){
			var node = xmlNode.childNodes[i];			
			if(node.nodeName == "processing"){
				for(var j=0; j<node.childNodes.length; j++){
					var processNode = node.childNodes[j];
					if(processNode.nodeName == "clientForm"){
						this.options.formId = processNode.getAttribute("id");
						this.options.formCode = processNode.firstChild.nodeValue;
						this.insertForm();
					}else if(processNode.nodeName == "clientCallback" && processNode.firstChild){
						this.options.callbackCode = '<script>'+processNode.firstChild.nodeValue+'</script>';
						if(processNode.getAttribute('prepareModal') && processNode.getAttribute('prepareModal') == "true"){
							this.options.prepareModal = true;						
						}
						if(processNode.getAttribute('displayModeButton') && processNode.getAttribute('displayModeButton') != ''){
							this.options.displayAction = processNode.getAttribute('displayModeButton');
						}						
					}else if(processNode.nodeName == "clientListener" && processNode.firstChild){
						if(!this.options.listeners) this.options.listeners = [];
						this.options.listeners[processNode.getAttribute('name')] = '<script>'+processNode.firstChild.nodeValue+'</script>';
					}
				}
			}else if(node.nodeName == "gui"){
				this.options.text = MessageHash[node.getAttribute('text')];
				if (typeof this.options.text == 'undefined')
					this.options.text = node.getAttribute('text');
				this.options.title = MessageHash[node.getAttribute('title')];
				if (typeof this.options.title == 'undefined')
					this.options.title = node.getAttribute('title');
				this.options.src = node.getAttribute('src');				
				if(node.getAttribute('hasAccessKey') && node.getAttribute('hasAccessKey') == "true"){
					this.options.accessKey = node.getAttribute('accessKey');
					this.options.hasAccessKey = true;
				}
				for(var j=0; j<node.childNodes.length;j++){
					if(node.childNodes[j].nodeName == "context"){
						this.attributesToObject(this.context, node.childNodes[j]);
						try {
							var tmp = node.childNodes[j].getAttribute("ulteoMimes");
							if (tmp != null)
								this.isUlteoApplication = true;
						}
						catch(e) {}
					}
					else if(node.childNodes[j].nodeName == "selectionContext"){
						this.attributesToObject(this.selectionContext, node.childNodes[j]);
					}
				}
							
			}else if(node.nodeName == "rightsContext"){
				this.attributesToObject(this.rightsContext, node);
			}
		}
		if(!this.options.hasAccessKey) return;
		if(this.options.accessKey == '' 
			|| !MessageHash[this.options.accessKey] 
			|| this.options.text.indexOf(MessageHash[this.options.accessKey]) == -1)
		{
			this.options.accessKey == this.options.text.charAt(0);
		}else{
			this.options.accessKey = MessageHash[this.options.accessKey];
		}		
	}, 
	
	toActionBar:function(){
		var button = new Element('a', {
			href:this.options.name,
			id:this.options.name +'_button'
		}).observe('click', function(e){
			Event.stop(e);
			this.apply();
		}.bind(this));
		var imgPath = resolveImageSource(this.options.src,this.__DEFAULT_ICON_PATH, 22);
		var img = new Element('img', {
			id:this.options.name +'_button_icon',
			src:imgPath,
			width:18,
			height:18,
			border:0,
			align:'absmiddle',
			alt:this.options.title,
			title:this.options.title
		});
		var titleSpan = new Element('span', {id:this.options.name+'_button_label'}).setStyle({paddingLeft:6,paddingRight:6, cursor:'pointer'});
		button.insert(img).insert(new Element('br')).insert(titleSpan.update(this.getKeyedText()));
		this.elements.push(button);
		button.observe("mouseover", function(){
			if(button.hasClassName('disabled')) return;
			if(this.hideTimeout) clearTimeout(this.hideTimeout);
			new Effect.Morph(img, {
				style:'width:25px; height:25px;margin-top:0px;',
				duration:0.08,
				transition:Effect.Transitions.sinoidal,
				afterFinish: function(){this.updateTitleSpan(titleSpan, 'big');}.bind(this)
			});
		}.bind(this) );
		button.observe("mouseout", function(){
			if(button.hasClassName('disabled')) return;
			this.hideTimeout = setTimeout(function(){				
				new Effect.Morph(img, {
					style:'width:18px; height:18px;margin-top:8px;',
					duration:0.2,
					transition:Effect.Transitions.sinoidal,
					afterFinish: function(){this.updateTitleSpan(titleSpan, 'small');}.bind(this)
				});	
			}.bind(this), 10);
		}.bind(this) );
		button.hide();
		return button;
	},
	
	updateTitleSpan : function(span, state){		
		if(!span.orig_width && state == 'big'){
			var origWidth = span.getWidth();
			span.setStyle({display:'block',width:origWidth, overflow:'visible', padding:0});
			span.orig_width = origWidth;
		}
		span.setStyle({fontSize:(state=='big'?'11px':'9px')});
	},
	
	setIconSrc : function(newSrc){
		this.options.src = newSrc;
		if($(this.options.name +'_button_icon')){
			$(this.options.name +'_button_icon').src = resolveImageSource(this.options.src,this.__DEFAULT_ICON_PATH, 22);
		}		
	},
	
	setLabel : function(newLabel, newTitle){
		this.options.text = MessageHash[newLabel];
		if($(this.options.name+'_button_label')){
			$(this.options.name+'_button_label').update(this.getKeyedText());
		}
		if(!newTitle) return;
		this.options.title = MessageHash[newTitle];
		if($(this.options.name+'_button_icon')){
			$(this.options.name+'_button_icon').title = this.options.title;
		}
	},
	
	toInfoPanel:function(){
		return this.options;
	},
	
	toContextMenu:function(){
		return this.options;
	},
	
	hideForContext: function(){
		this.hide();
		this.contextHidden = true;
	},
	
	showForContext: function(){
		this.show();
		this.contextHidden = false;
	},
	
	hide: function(){		
		if(this.elements.size() > 0 || (!this.context.actionBar && this.context.infoPanel)) this.deny = true;
		this.elements.each(function(elem){
			elem.hide();
		});
	},
	
	show: function(){
		if(this.elements.size() > 0 || (!this.context.actionBar && this.context.infoPanel)) this.deny = false;
		this.elements.each(function(elem){
			elem.show();
		});
	},
	
	disable: function(){
		if(this.elements.size() > 0 || (!this.context.actionBar && this.context.infoPanel)) this.deny = true;
		this.elements.each(function(elem){
			elem.addClassName('disabled');
		});	
	},
	
	enable: function(){
		if(this.elements.size() > 0 || (!this.context.actionBar && this.context.infoPanel)) this.deny = false;
		this.elements.each(function(elem){
			elem.removeClassName('disabled');
		});	
	},
	
	remove: function(){
		// Remove all elements and forms from html
		this.elements.each(function(el){
			$(el).remove();
		});
		if(this.options.formId && $('all_forms').select('[id="'+this.options.formId+'"]').length){
			$('all_forms').select('[id="'+this.options.formId+'"]')[0].remove();
		}
	},
	
	getKeyedText: function(){
		var displayString = this.options.text;
		if(!this.options.hasAccessKey) return displayString;
		var accessKey = this.options.accessKey;
		var keyPos = displayString.toLowerCase().indexOf(accessKey.toLowerCase());
		if(keyPos==-1){
			return displayString + ' (<u>' + accessKey + '</u>)';
		}
		if(displayString.charAt(keyPos) != accessKey){
			// case differ
			accessKey = displayString.charAt(keyPos);
		}
		returnString = displayString.substring(0,displayString.indexOf(accessKey));
		returnString += '<u>'+accessKey+'</u>';
		returnString += displayString.substring(displayString.indexOf(accessKey)+1, displayString.length);
		return returnString;
	},
	
	insertForm: function(){
		if(!this.options.formCode || !this.options.formId) return;
		if($('all_forms').select('[id="'+this.options.formId+'"]').length) return;
		$('all_forms').insert(this.options.formCode);
	},
	
	attributesToObject: function(object, node){
		Object.keys(object).each(function(key){
			if(node.getAttribute(key)){
				value = node.getAttribute(key);
				if(value == 'true') value = true;
				else if(value == 'false') value = false;
				if(key == 'allowedMimes'){
					if(value && value.split(',').length){
						value = $A(value.split(','));
					}else{
						value = $A([]);
					}					
				}
				if(key == 'ulteoMimes'){
					if(value && value.split(',').length){
						value = $A(value.split(','));
					}else{
						value = $A([]);
					}
				}
				this[key] = value;
			}
		}.bind(object));
	}

});

// Mime types
MimeType = {
	'%': 'application/x-trash',
	'323': 'text/h323',
	'3gp': 'video/3gpp',
	'7z': 'application/x-7z-compressed',
	'abw': 'application/x-abiword',
	'ai': 'application/postscript',
	'aif': 'audio/x-aiff',
	'aifc': 'audio/x-aiff',
	'aiff': 'audio/x-aiff',
	'alc': 'chemical/x-alchemy',
	'amr': 'audio/amr',
	'anx': 'application/annodex',
	'arj': 'application/x-arj-compressed',
	'art': 'image/x-jg',
	'asc': 'text/plain',
	'asf': 'video/x-ms-asf',
	'asn': 'chemical/x-ncbi-asn1-spec',
	'aso': 'chemical/x-ncbi-asn1-binary',
	'asx': 'video/x-ms-asf',
	'atom': 'application/atom+xml',
	'atomcat': 'application/atomcat+xml',
	'atomsrv': 'application/atomserv+xml',
	'au': 'audio/basic',
	'avi': 'video/x-msvideo',
	'awb': 'audio/amr-wb',
	'axa': 'audio/annodex',
	'axv': 'video/annodex',
	'b': 'chemical/x-molconn-Z',
	'bak': 'application/x-trash',
	'bat': 'application/x-msdos-program',
	'bcpio': 'application/x-bcpio',
	'bib': 'text/x-bibtex',
	'bin': 'application/octet-stream',
	'bmp': 'image/bmp',
	'boo': 'text/x-boo',
	'book': 'application/x-maker',
	'brf': 'text/plain',
	'bsd': 'chemical/x-crossfire',
	'c': 'text/x-csrc',
	'c++': 'text/x-c++src',
	'c3d': 'chemical/x-chem3d',
	'cab': 'application/x-cab',
	'cac': 'chemical/x-cache',
	'cache': 'chemical/x-cache',
	'cap': 'application/cap',
	'cascii': 'chemical/x-cactvs-binary',
	'cat': 'application/vnd.ms-pki.seccat',
	'cbin': 'chemical/x-cactvs-binary',
	'cbr': 'application/x-cbr',
	'cbz': 'application/x-cbz',
	'cc': 'text/x-c++src',
	'ccad': 'application/clariscad',
	'cda': 'application/x-cdf',
	'cdf': 'application/x-cdf',
	'cdr': 'image/x-coreldraw',
	'cdt': 'image/x-coreldrawtemplate',
	'cdx': 'chemical/x-cdx',
	'cdy': 'application/vnd.cinderella',
	'cef': 'chemical/x-cxf',
	'cer': 'chemical/x-cerius',
	'chm': 'chemical/x-chemdraw',
	'chrt': 'application/x-kchart',
	'cif': 'chemical/x-cif',
	'class': 'application/java-vm',
	'cls': 'text/x-tex',
	'cmdf': 'chemical/x-cmdf',
	'cml': 'chemical/x-cml',
	'cod': 'application/vnd.rim.cod',
	'com': 'application/x-msdos-program',
	'cpa': 'chemical/x-compass',
	'cpio': 'application/x-cpio',
	'cpp': 'text/x-c++src',
	'cpt': 'image/x-corelphotopaint',
	'crl': 'application/x-pkcs7-crl',
	'crt': 'application/x-x509-ca-cert',
	'csf': 'chemical/x-cache-csf',
	'csh': 'text/x-csh',
	'csm': 'chemical/x-csml',
	'csml': 'chemical/x-csml',
	'css': 'text/css',
	'csv': 'text/csv',
	'ctab': 'chemical/x-cactvs-binary',
	'ctx': 'chemical/x-ctx',
	'cu': 'application/cu-seeme',
	'cub': 'chemical/x-gaussian-cube',
	'cxf': 'chemical/x-cxf',
	'cxx': 'text/x-c++src',
	'd': 'text/x-dsrc',
	'dat': 'application/x-ns-proxy-autoconfig',
	'davmount': 'application/davmount+xml',
	'dcr': 'application/x-director',
	'deb': 'application/x-debian-package',
	'dif': 'video/dv',
	'diff': 'text/x-diff',
	'dir': 'application/x-director',
	'djv': 'image/vnd.djvu',
	'djvu': 'image/vnd.djvu',
	'dl': 'video/dl',
	'dll': 'application/x-msdos-program',
	'dmg': 'application/x-apple-diskimage',
	'dms': 'application/x-dms',
	'doc': 'application/msword',
	'dot': 'application/msword',
	'drw': 'application/drafting',
	'dv': 'video/dv',
	'dvi': 'application/x-dvi',
	'dwg': 'application/acad',
	'dx': 'chemical/x-jcamp-dx',
	'dxf': 'application/dxf',
	'dxr': 'application/x-director',
	'emb': 'chemical/x-embl-dl-nucleotide',
	'embl': 'chemical/x-embl-dl-nucleotide',
	'eml': 'message/rfc822',
	'ent': 'chemical/x-pdb',
	'eps': 'application/postscript',
	'eps2': 'application/postscript',
	'eps3': 'application/postscript',
	'epsf': 'application/postscript',
	'es': 'application/ecmascript',
	'espi': 'application/postscript',
	'etx': 'text/x-setext',
	'exe': 'application/x-msdos-program',
	'ez': 'application/andrew-inset',
	'f': 'text/plain',
	'f90': 'text/plain',
	'fb': 'application/x-maker',
	'fbdoc': 'application/x-maker',
	'fch': 'chemical/x-gaussian-checkpoint',
	'fchk': 'chemical/x-gaussian-checkpoint',
	'fig': 'application/x-xfig',
	'flac': 'audio/flac',
	'fli': 'video/fli',
	'flv': 'video/x-flv',
	'fm': 'application/x-maker',
	'frame': 'application/x-maker',
	'frm': 'application/x-maker',
	'gal': 'chemical/x-gaussian-log',
	'gam': 'chemical/x-gamess-input',
	'gamin': 'chemical/x-gamess-input',
	'gau': 'chemical/x-gaussian-input',
	'gcd': 'text/x-pcs-gcd',
	'gcf': 'application/x-graphing-calculator',
	'gcg': 'chemical/x-gcg8-sequence',
	'gen': 'chemical/x-genbank',
	'gf': 'application/x-tex-gf',
	'gif': 'image/gif',
	'gjc': 'chemical/x-gaussian-input',
	'gjf': 'chemical/x-gaussian-input',
	'gl': 'video/gl',
	'gnumeric': 'application/x-gnumeric',
	'gpt': 'chemical/x-mopac-graph',
	'gsf': 'application/x-font',
	'gsm': 'audio/x-gsm',
	'gtar': 'application/x-gtar',
	'gz': 'application/x-gzip',
	'h': 'text/x-chdr',
	'h++': 'text/x-c++hdr',
	'hdf': 'application/x-hdf',
	'hh': 'text/x-c++hdr',
	'hin': 'chemical/x-hin',
	'hpp': 'text/x-c++hdr',
	'hqx': 'application/mac-binhex40',
	'hs': 'text/x-haskell',
	'hta': 'application/hta',
	'htc': 'text/x-component',
	'htm': 'text/html',
	'html': 'text/html',
	'hxx': 'text/x-c++hdr',
	'ica': 'application/x-ica',
	'ice': 'x-conference/x-cooltalk',
	'ico': 'image/x-icon',
	'ics': 'text/calendar',
	'icz': 'text/calendar',
	'ief': 'image/ief',
	'iges': 'model/iges',
	'igs': 'model/iges',
	'iii': 'application/x-iphone',
	'info': 'application/x-info',
	'inp': 'chemical/x-gamess-input',
	'ins': 'application/x-internet-signup',
	'ips': 'application/x-ipscript',
	'ipx': 'application/x-ipix',
	'iso': 'application/x-iso9660-image',
	'isp': 'application/x-internet-signup',
	'ist': 'chemical/x-isostar',
	'istr': 'chemical/x-isostar',
	'jad': 'text/vnd.sun.j2me.app-descriptor',
	'jam': 'application/x-jam',
	'jar': 'application/java-archive',
	'java': 'text/x-java',
	'jdx': 'chemical/x-jcamp-dx',
	'jmz': 'application/x-jmol',
	'jng': 'image/x-jng',
	'jnlp': 'application/x-java-jnlp-file',
	'jpe': 'image/jpeg',
	'jpeg': 'image/jpeg',
	'jpg': 'image/jpeg',
	'js': 'application/javascript',
	'kar': 'audio/midi',
	'key': 'application/pgp-keys',
	'kil': 'application/x-killustrator',
	'kin': 'chemical/x-kinemage',
	'kml': 'application/vnd.google-earth.kml+xml',
	'kmz': 'application/vnd.google-earth.kmz',
	'kpr': 'application/x-kpresenter',
	'kpt': 'application/x-kpresenter',
	'ksp': 'application/x-kspread',
	'kwd': 'application/x-kword',
	'kwt': 'application/x-kword',
	'latex': 'application/x-latex',
	'lha': 'application/x-lha',
	'lhs': 'text/x-literate-haskell',
	'lin': 'application/bbolin',
	'lsf': 'video/x-la-asf',
	'lsp': 'application/x-lisp',
	'lsx': 'video/x-la-asf',
	'ltx': 'text/x-tex',
	'lyx': 'application/x-lyx',
	'lzh': 'application/x-lzh',
	'lzx': 'application/x-lzx',
	'm': 'text/plain',
	'm3g': 'application/m3g',
	'm3u': 'audio/x-mpegurl',
	'm4a': 'audio/mpeg',
	'maker': 'application/x-maker',
	'man': 'application/x-troff-man',
	'mcif': 'chemical/x-mmcif',
	'mcm': 'chemical/x-macmolecule',
	'mdb': 'application/msaccess',
	'me': 'application/x-troff-me',
	'mesh': 'model/mesh',
	'mid': 'audio/midi',
	'midi': 'audio/midi',
	'mif': 'application/x-mif',
	'mime': 'www/mime',
	'mm': 'application/x-freemind',
	'mmd': 'chemical/x-macromodel-input',
	'mmf': 'application/vnd.smaf',
	'mml': 'text/mathml',
	'mmod': 'chemical/x-macromodel-input',
	'mng': 'video/x-mng',
	'moc': 'text/x-moc',
	'mol': 'chemical/x-mdl-molfile',
	'mol2': 'chemical/x-mol2',
	'moo': 'chemical/x-mopac-out',
	'mop': 'chemical/x-mopac-input',
	'mopcrt': 'chemical/x-mopac-input',
	'mov': 'video/quicktime',
	'movie': 'video/x-sgi-movie',
	'mp2': 'audio/mpeg',
	'mp3': 'audio/mpeg',
	'mp4': 'video/mp4',
	'mpc': 'chemical/x-mopac-input',
	'mpe': 'video/mpeg',
	'mpeg': 'video/mpeg',
	'mpega': 'audio/mpeg',
	'mpg': 'video/mpeg',
	'mpga': 'audio/mpeg',
	'mpv': 'video/x-matroska',
	'ms': 'application/x-troff-ms',
	'msh': 'model/mesh',
	'msi': 'application/x-msi',
	'mvb': 'chemical/x-mopac-vib',
	'mxu': 'video/vnd.mpegurl',
	'nb': 'application/mathematica',
	'nbp': 'application/mathematica',
	'nc': 'application/x-netcdf',
	'nwc': 'application/x-nwc',
	'o': 'application/x-object',
	'oda': 'application/oda',
	'odb': 'application/vnd.oasis.opendocument.database',
	'odc': 'application/vnd.oasis.opendocument.chart',
	'odf': 'application/vnd.oasis.opendocument.formula',
	'odg': 'application/vnd.oasis.opendocument.graphics',
	'odi': 'application/vnd.oasis.opendocument.image',
	'odm': 'application/vnd.oasis.opendocument.text-master',
	'odp': 'application/vnd.oasis.opendocument.presentation',
	'ods': 'application/vnd.oasis.opendocument.spreadsheet',
	'odt': 'application/vnd.oasis.opendocument.text',
	'oga': 'audio/ogg',
	'ogg': 'audio/ogg',
	'ogm': 'application/ogg',
	'ogv': 'video/ogg',
	'ogx': 'application/ogg',
	'old': 'application/x-trash',
	'otg': 'application/vnd.oasis.opendocument.graphics-template',
	'oth': 'application/vnd.oasis.opendocument.text-web',
	'otp': 'application/vnd.oasis.opendocument.presentation-template',
	'ots': 'application/vnd.oasis.opendocument.spreadsheet-template',
	'ott': 'application/vnd.oasis.opendocument.text-template',
	'oza': 'application/x-oz-application',
	'p': 'text/x-pascal',
	'p7r': 'application/x-pkcs7-certreqresp',
	'pac': 'application/x-ns-proxy-autoconfig',
	'pas': 'text/x-pascal',
	'pat': 'image/x-coreldrawpattern',
	'patch': 'text/x-diff',
	'pbm': 'image/x-portable-bitmap',
	'pcap': 'application/cap',
	'pcf': 'application/x-font',
	'pcf.Z': 'application/x-font',
	'pcx': 'image/pcx',
	'pdb': 'chemical/x-pdb',
	'pdf': 'application/pdf',
	'pfa': 'application/x-font',
	'pfb': 'application/x-font',
	'pgm': 'image/x-portable-graymap',
	'pgn': 'application/x-chess-pgn',
	'pgp': 'application/pgp-signature',
	'php': 'application/x-httpd-php',
	'php3': 'application/x-httpd-php3',
	'php3p': 'application/x-httpd-php3-preprocessed',
	'php4': 'application/x-httpd-php4',
	'phps': 'application/x-httpd-php-source',
	'pht': 'application/x-httpd-php',
	'phtml': 'application/x-httpd-php',
	'pk': 'application/x-tex-pk',
	'pl': 'text/x-perl',
	'pls': 'audio/x-scpls',
	'pm': 'text/x-perl',
	'png': 'image/png',
	'pnm': 'image/x-portable-anymap',
	'pot': 'text/plain',
	'ppm': 'image/x-portable-pixmap',
	'pps': 'application/vnd.ms-powerpoint',
	'ppt': 'application/vnd.ms-powerpoint',
	'ppz': 'application/vnd.ms-powerpoint',
	'pre': 'application/x-freelance',
	'prf': 'application/pics-rules',
	'prt': 'chemical/x-ncbi-asn1-ascii',
	'ps': 'application/postscript',
	'psd': 'image/x-photoshop',
	'py': 'text/x-python',
	'pyc': 'application/x-python-code',
	'pyo': 'application/x-python-code',
	'qgs': 'application/x-qgis',
	'qt': 'video/quicktime',
	'qtl': 'application/x-quicktimeplayer',
	'ra': 'audio/x-realaudio',
	'ram': 'audio/x-pn-realaudio',
	'rar': 'application/rar',
	'ras': 'image/x-cmu-raster',
	'rb': 'application/x-ruby',
	'rd': 'chemical/x-mdl-rdfile',
	'rdf': 'application/rdf+xml',
	'rgb': 'image/x-rgb',
	'rhtml': 'application/x-httpd-eruby',
	'rm': 'audio/x-pn-realaudio',
	'roff': 'application/x-troff',
	'ros': 'chemical/x-rosdal',
	'rpm': 'application/x-redhat-package-manager',
	'rss': 'application/rss+xml',
	'rtf': 'application/rtf',
	'rtx': 'text/richtext',
	'rxn': 'chemical/x-mdl-rxnfile',
	'scala': 'text/x-scala',
	'scm': 'application/x-lotusscreencam',
	'sct': 'text/scriptlet',
	'sd': 'chemical/x-mdl-sdfile',
	'sd2': 'audio/x-sd2',
	'sda': 'application/vnd.stardivision.draw',
	'sdc': 'application/vnd.stardivision.calc',
	'sdd': 'application/vnd.stardivision.impress',
	'sdf': 'chemical/x-mdl-sdfile',
	'sds': 'application/vnd.stardivision.chart',
	'sdw': 'application/vnd.stardivision.writer',
	'ser': 'application/java-serialized-object',
	'set': 'application/set',
	'sgf': 'application/x-go-sgf',
	'sgl': 'application/vnd.stardivision.writer-global',
	'sgm': 'text/sgml',
	'sgml': 'text/sgml',
	'sh': 'text/x-sh',
	'shar': 'application/x-shar',
	'shp': 'application/x-qgis',
	'shtml': 'text/html',
	'shx': 'application/x-qgis',
	'sid': 'audio/prs.sid',
	'sik': 'application/x-trash',
	'silo': 'model/mesh',
	'sis': 'application/vnd.symbian.install',
	'sisx': 'x-epoc/x-sisx-app',
	'sit': 'application/x-stuffit',
	'sitx': 'application/x-stuffit',
	'skd': 'application/x-koan',
	'skm': 'application/x-koan',
	'skp': 'application/x-koan',
	'skt': 'application/x-koan',
	'smi': 'application/smil',
	'smil': 'application/smil',
	'snd': 'audio/basic',
	'sol': 'application/solids',
	'spc': 'chemical/x-galactic-spc',
	'spl': 'application/x-futuresplash',
	'spx': 'audio/ogg',
	'src': 'application/x-wais-source',
	'stc': 'application/vnd.sun.xml.calc.template',
	'std': 'application/vnd.sun.xml.draw.template',
	'step': 'application/STEP',
	'sti': 'application/vnd.sun.xml.impress.template',
	'stl': 'application/vnd.ms-pki.stl',
	'stp': 'application/STEP',
	'stw': 'application/vnd.sun.xml.writer.template',
	'sty': 'text/x-tex',
	'sv4cpio': 'application/x-sv4cpio',
	'sv4crc': 'application/x-sv4crc',
	'svg': 'image/svg+xml',
	'svgz': 'image/svg+xml',
	'sw': 'chemical/x-swissprot',
	'swf': 'application/x-shockwave-flash',
	'swfl': 'application/x-shockwave-flash',
	'sxc': 'application/vnd.sun.xml.calc',
	'sxd': 'application/vnd.sun.xml.draw',
	'sxg': 'application/vnd.sun.xml.writer.global',
	'sxi': 'application/vnd.sun.xml.impress',
	'sxm': 'application/vnd.sun.xml.math',
	'sxw': 'application/vnd.sun.xml.writer',
	't': 'application/x-troff',
	'tar': 'application/x-tar',
	'tar.gz': 'application/x-tar-gz',
	'taz': 'application/x-gtar',
	'tcl': 'text/x-tcl',
	'tex': 'text/x-tex',
	'texi': 'application/x-texinfo',
	'texinfo': 'application/x-texinfo',
	'text': 'text/plain',
	'tgf': 'chemical/x-mdl-tgf',
	'tgz': 'application/x-gtar',
	'tif': 'image/tiff',
	'tiff': 'image/tiff',
	'tk': 'text/x-tcl',
	'tm': 'text/texmacs',
	'torrent': 'application/x-bittorrent',
	'tr': 'application/x-troff',
	'ts': 'text/texmacs',
	'tsi': 'audio/TSP-audio',
	'tsp': 'application/dsptype',
	'tsv': 'text/tab-separated-values',
	'txt': 'text/plain',
	'udeb': 'application/x-debian-package',
	'uls': 'text/iuls',
	'unv': 'application/i-deas',
	'ustar': 'application/x-ustar',
	'val': 'chemical/x-ncbi-asn1-binary',
	'vcd': 'application/x-cdlink',
	'vcf': 'text/x-vcard',
	'vcs': 'text/x-vcalendar',
	'vda': 'application/vda',
	'viv': 'video/vnd.vivo',
	'vivo': 'video/vnd.vivo',
	'vmd': 'chemical/x-vmd',
	'vms': 'chemical/x-vamas-iso14976',
	'vrm': 'x-world/x-vrml',
	'vrml': 'x-world/x-vrml',
	'vsd': 'application/vnd.visio',
	'wad': 'application/x-doom',
	'wav': 'audio/x-wav',
	'wax': 'audio/x-ms-wax',
	'wbmp': 'image/vnd.wap.wbmp',
	'wbxml': 'application/vnd.wap.wbxml',
	'wk': 'application/x-123',
	'wm': 'video/x-ms-wm',
	'wma': 'audio/x-ms-wma',
	'wmd': 'application/x-ms-wmd',
	'wml': 'text/vnd.wap.wml',
	'wmlc': 'application/vnd.wap.wmlc',
	'wmls': 'text/vnd.wap.wmlscript',
	'wmlsc': 'application/vnd.wap.wmlscriptc',
	'wmv': 'video/x-ms-wmv',
	'wmx': 'video/x-ms-wmx',
	'wmz': 'application/x-ms-wmz',
	'wp5': 'application/vnd.wordperfect5.1',
	'wpd': 'application/vnd.wordperfect',
	'wrl': 'x-world/x-vrml',
	'wsc': 'text/scriptlet',
	'wvx': 'video/x-ms-wvx',
	'wz': 'application/x-wingz',
	'xbm': 'image/x-xbitmap',
	'xcf': 'application/x-xcf',
	'xht': 'application/xhtml+xml',
	'xhtml': 'application/xhtml+xml',
	'xlb': 'application/vnd.ms-excel',
	'xlc': 'application/vnd.ms-excel',
	'xll': 'application/vnd.ms-excel',
	'xlm': 'application/vnd.ms-excel',
	'xls': 'application/vnd.ms-excel',
	'xlt': 'application/vnd.ms-excel',
	'xlw': 'application/vnd.ms-excel',
	'xml': 'application/xml',
	'xpi': 'application/x-xpinstall',
	'xpm': 'image/x-xpixmap',
	'xsd': 'application/xml',
	'xsl': 'application/xml',
	'xspf': 'application/xspf+xml',
	'xtel': 'chemical/x-xtel',
	'xul': 'application/vnd.mozilla.xul+xml',
	'xwd': 'image/x-xwindowdump',
	'xyz': 'chemical/x-xyz',
	'zip': 'application/zip',
	'zmt': 'chemical/x-mopac-input',
	'~': 'application/x-trash'
};

function getExtension(path_) {
	var buffer = path_.split('.');
	var buf = buffer[buffer.length-1];
	return buf.toLowerCase();
}

function getMimeType(path_) {
	var buf = getExtension(path_);

	try {
		return MimeType[buf];
	} catch(e) {
		return null;
	}
}
