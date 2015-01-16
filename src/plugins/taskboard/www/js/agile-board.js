var aTaskboardUserStories = [];

function cleanMessages() {
	jQuery('#messages').html('').hide();
}

function showMessage( msg_text, msg_class) {
	jQuery('#messages').removeClass().addClass( msg_class ).html( msg_text ).show();
}

function loadTaskboard( group_id ) {
	var assigned_to = jQuery('select[name="_assigned_to"]').val();
	var release = jQuery('select[name="_release"]').val();
	var data = {
			action   : 'load_taskboard',
			group_id : group_id,
			assigned_to : assigned_to,
			release : release
		};

	jQuery.ajax({
		type: 'POST',
		url: gAjaxUrl,
		dataType: 'json',
		data : data, 
		async: false
	}).done(function( answer ) {
		jQuery('#agile-board tbody').html('');
		jQuery('#agile-board-progress').html( '' );

		if(answer['message']) {
			showMessage(answer['message'], 'error');
		}

		aUserStories = answer['user_stories'];
		aPhases = answer['phases'];

		if( aUserStories.length ) {
			jQuery( "#agile-board" ).append(
				drawUserStories()
			);
	
			jQuery( ".agile-toolbar-add-task" ).click( function(e) {
				jQuery('#new-task-dialog input[name="user_story_id"]').val( jQuery(this).attr('user_story_id') );
				jQuery('#new-task-dialog').dialog('open');
				jQuery('.ui-widget-overlay').height( jQuery( document ).height() );
				e.preventDefault();	
			});

		
			for(var i=0 ; i<aUserStories.length ; i++) {
				drawUserStory( aUserStories[i] );
			};
			
			drawBoardProgress();
	
			initUserStories();
	
			initEditable();
			
			// set fixed with in pixels for preventing bad view when d'n'd
			jQuery('.agile-sticker').each( function( i, e) {
				jQuery(e).css('width',jQuery(e).width() );
			});
			
			jQuery( ".agile-minimize-column" ).click( function() {
				var phase_id = jQuery(this).attr('phase_id');
				var phase_locator = '.agile-phase-' + phase_id;
				var locator = phase_locator + ' div.agile-sticker-container';

				if( jQuery(this).is(':checked') ) {
					// hide column
					jQuery(phase_locator).each( function(j,p) {
						jQuery(p).children('div.agile-sticker-container').each( function(i,e) {
							if( i > 0 ) {
								jQuery(e).hide();
								if( i==1 && jQuery(e).parent().children(phase_locator+'-more').length == 0 ) {
									jQuery(e).parent().append('<div class="agile-phase-' + phase_id + '-more agile-phase-more">...</div>' );
								}
							}
						})
					});
				} else {
					// unhide column
					jQuery(locator).each( function(i,e) {
						jQuery(e).show();
						if( i==0 && jQuery(e).parent().children(phase_locator+'-more').length > 0 ) {
							jQuery(phase_locator+'-more').remove();
						}
					});
				}
			});
		} else {
			jQuery( "#agile-board" ).append(
					'<tr><td colspan="'  + aPhases.length + '" style="text-align: center;">' + gMessages['notasks'] + '</td></tr>'
				);
		}
	});
}

function drawBoardProgress() {
	var start= bShowUserStories ? 1 : 0;

	var totalTasks = 0;
	var totalCostEstimated = 0;
	var totalCostRemaining = 0;
	
	var lastPhaseWithTasks = 0;
	for( var j=start; j<aPhases.length; j++) {
		aPhases[j].progressTasks = 0;
		aPhases[j].progressCost = 0;
		for( var i=0; i<aUserStories.length; i++ ) {
			for( var t=0; t<aUserStories[i].tasks.length; t++ ) {
				if( taskInPhase( aUserStories[i].tasks[t], aPhases[j].id ) ) {
					aPhases[j].progressTasks ++;
					totalTasks ++;
					
					if( aUserStories[i].tasks[t].estimated_dev_effort ) {
						totalCostEstimated += parseInt( aUserStories[i].tasks[t].estimated_dev_effort );
						totalCostRemaining += parseInt( aUserStories[i].tasks[t].remaining_dev_effort );
					}
					
					lastPhaseWithTasks = j;
				}
			}
		}
	}

	var html = '<table>';
	html += '<tr><td width="' + parseInt( 100 / aPhases.length )  + '%" style="padding: 0;">' + gMessages.progressByTasks + ':</td><td style="padding: 0;">';
	
	var buf = 0;
	for( var j=start; j<aPhases.length; j++) {
		if( aPhases[j].progressTasks ) {
			var wt = parseInt(  parseInt( aPhases[j].progressTasks ) / totalTasks * 100 );
			if( j == lastPhaseWithTasks ) {
				// to avoid bad presenttaion when sum of rounded percemts less then 100
				wt = 100 - buf;
			}
			buf += wt;
			
			var back = '';
			if( aPhases[j].titlebackground ) {
				back = 'background-color:' + aPhases[j].titlebackground;
			}
			html += '<div class="agile-board-progress-bar" style="width: ' + wt + '%; ' + back + '" ' + 'title="' + aPhases[j].title + '">' + 
				aPhases[j].progressTasks + 
				'</div>';
		}
	}

	html += '</td></tr><table>';

	if( parseInt(totalCostEstimated) > 0 ) {
		var totalCostCompleted = totalCostEstimated - totalCostRemaining;
		var wt = parseInt( totalCostCompleted/totalCostEstimated * 100);
		// show progress by cost
		html += '<table>';
		html += '<tr><td width="' + parseInt( 100 / aPhases.length )  + '%" style="padding: 0;">' + gMessages.progressByCost + ':</td><td style="padding: 0;">';
		html += '<div class="agile-board-progress-bar-done" style="width: ' + wt + '%;">' + totalCostCompleted + '</div>';
		html += '<div class="agile-board-progress-bar-remains" style="width: ' + ( 100 - wt ) + '%;">' + totalCostRemaining + '</div>';
		html += '</td></tr><table>';
	}
	
	jQuery('#agile-board-progress').html( html );
}

function drawUserStories() {
	var l_sHtml = '';

	for( var i=0; i<aUserStories.length; i++ ) {
		l_sHtml += "<tr valign='top'>\n";
		var start=0;
		var us=aUserStories[i];
		if( bShowUserStories ) {
			start=1;
			l_sHtml += '<td class="agile-phase"><div class="agile-sticker-container">';
			l_sHtml += '<div class="agile-sticker agile-sticker-user-story" id="user-story-' + us.id + '">';
			l_sHtml += '<div class="agile-sticker-header"><a href="' + us.url + '" target="_blank">' + us.id + '</a> : <span>' + us.title + "</span>";
			l_sHtml += '<div style="float: right";>[<a href="" class="agile-toolbar-add-task" user_story_id="' +us.id+ '">+</a>]</div></div>\n';
			l_sHtml += '<div class="agile-sticker-body">' + us.description + "</div>\n";
			l_sHtml += "</div>\n";
			l_sHtml += "</td>\n";
		}

		for( var j=start; j<aPhases.length; j++) {
			var ph=aPhases[j];
			var style = '';
			if( ph.background ) {
				style = ' style="background-color:' + ph.background + ';"';
			}
			l_sHtml += '<td id="' + ph.id + '-' + us.id + '" class="agile-phase agile-phase-' + ph.id + '"' + style + '>';
			l_sHtml += "</td>\n";
			
			// initialize progress counters
			aPhases[j].progressTasks = 0;
			aPhases[j].progressCost = 0;
		}

		l_sHtml += "</tr>\n";
	}

	return l_sHtml;
}

function helperTaskStart ( event, ui ) {
	jQuery(this).css('opacity', 0.5);
}

function helperTaskStop ( event, ui ) {	
	jQuery(this).css('opacity', 1);
}

function helperTaskDrop ( event, ui ) {
	var d = jQuery( ui.draggable );
	if( d.length ) {
		var l_nUserStoryId = d.data('user_story_id');
		var l_nTaskId      = d.data('task_id');
		var l_nTargetPhaseId     = jQuery(this).data('phase_id');
	
		setPhase( l_nUserStoryId, l_nTaskId, l_nTargetPhaseId );
	}
}

function setPhase( nUserStoryId, nTaskId, nTargetPhaseId ) {
	var l_oUserStory;
	var l_oTargetPhase;
	var l_nSourcePhaseId;

	for( var i=0; i<aPhases.length ; i++ ) {
		if( aPhases[i].id == nTargetPhaseId ) {
			l_oTargetPhase = aPhases[i];
		}
	}

	for( var i=0; i<aUserStories.length ; i++ ) {
		if( aUserStories[i].id == nUserStoryId ) {
			l_oUserStory = aUserStories[i];
			for( var j=0; j<aUserStories[i].tasks.length ; j++ ) {
				if( aUserStories[i].tasks[j].id == nTaskId ) {
					l_nSourcePhaseId = aUserStories[i].tasks[j].phase_id;
				
					if( l_oTargetPhase && l_nSourcePhaseId != nTargetPhaseId ) {
							// try to drop card 
							jQuery.ajax({
								type: 'POST',
								url: gAjaxUrl,
								dataType: 'json',
								data : {
									action   : 'drop_card',
									group_id : gGroupId,
									task_id : nTaskId,
									target_phase_id : nTargetPhaseId
								},
								async: false
							}).done(function( answer ) {
								if(answer['message']) {
									showMessage(answer['message'], 'error');
								}

								if(answer['alert']) {
									alert( answer['alert'] );
								}

								if(answer['action'] == 'reload') {
									// reload whole board
									loadTaskboard( gGroupId );
								} else {
									if( answer['task'] ) {
										// change particular task data
										aUserStories[i].tasks[j] = answer['task']; 
									}
	
									if( l_oUserStory ) {
										drawUserStory(l_oUserStory);
									}
									
									jQuery('.agile-sticker').each( function( i, e) {
										jQuery(e).css('width',jQuery(e).width() );
									});
	
									jQuery('#agile-board-progress').html( '' );
									drawBoardProgress();
	
									initEditable();
								}
							});
					}
				}	
			}			
		}
	} 
}

function initUserStories() {
	for( var i=0; i<aUserStories.length; i++ ) {
		initUserStory( aUserStories[i] );
	}
}

function initUserStory( oUserStory ) {
	for( var i=0; i<aPhases.length ; i++ ) {
		if( aPhases[i].id != 'user-stories') {
			var sPhaseId = "#" + aPhases[i].id + "-" + oUserStory.id;

			//make phase droppable
			if( gIsTechnician ) {
				jQuery( sPhaseId )
					.data('phase_id', aPhases[i].id)
					.droppable( {
						accept: '.agile-sticker-task-' + oUserStory.id,
						hoverClass: 'agile-phase-hovered',
						drop: helperTaskDrop
					} );
			}

			if( aPhases[i].background ) {
				jQuery("#" + aPhases[i].id + "-" + oUserStory.id).css('background-color', aPhases[i].background );
			}
		}
	}
	
	jQuery('#user-story-' + oUserStory.id).data('task_id', oUserStory.id);
}

function drawUserStory( oUserStory ) {

	for( var i=0; i<aPhases.length ; i++ ) {
		if( aPhases[i].id != 'user-stories') {
			var sPhaseId = "#" + aPhases[i].id + "-" + oUserStory.id;
			jQuery( sPhaseId ).html(
					drawTasks( oUserStory, aPhases[i].id )
			);
		}
	}

	// only technician can move tasks
	if( gIsTechnician ) {
		for(var j=0 ; j<oUserStory.tasks.length ; j++) {
			jQuery('#task-' + oUserStory.tasks[j].id)
				.data('task_id', oUserStory.tasks[j].id)
				.data('user_story_id', oUserStory.id)
				.draggable( {
					containment: '#agile-board',
					//cursor: 'move',
					stack: 'div',
					revert: true,
					start: helperTaskStart,
					stop: helperTaskStop,
					helper: "clone",
					distance: 10
				} );
		}
	}
}

function drawTasks( oUserStory, sPhaseId ) {
	var l_sHtml = '' ;

	for( var i=0; i<oUserStory.tasks.length; i++ ) {
		tsk = oUserStory.tasks[i];
		if( taskInPhase( tsk, sPhaseId ) ) {
			l_sHtml += '<div class="agile-sticker-container">';
			l_sHtml += '<div class="agile-sticker agile-sticker-task agile-sticker-task-' + tsk.user_story + '" id="task-' + tsk.id + '" >';
			l_sHtml += '<div class="agile-sticker-header" style="background-color: ' + tsk.background + ';">';
			l_sHtml += '<a href="' + tsk.url  +  '" target="_blank">' + tsk.id + '</a> : <span>' + tsk.title + '</span>';
			l_sHtml += "</div>\n";
			l_sHtml += '<div class="agile-sticker-body">' + tsk.description + '</div>';
 			l_sHtml += "</div></div>\n";
		}
	}

	return l_sHtml;
}

function taskInPhase( tsk, phase ) {
	if( tsk.phase_id ==  phase) {
		return true;
	}

	for( var i=0; i<aPhases.length; i++) {
		if( aPhases[i].id == phase && aPhases[i].resolutions ) {
			for( var j=0; j<aPhases[i].resolutions.length; j++) {
				if( tsk.resolution == aPhases[i].resolutions[j] ) {
					return true;
				}
			}
		}
	}

	return false;
}

function initEditable() {
	// only tracker manager can modify tasks
	if( !gIsManager ) {
		return;
	}

	// title
	jQuery("div.agile-sticker-header span").dblclick( function () {
		if( jQuery(this).children('input').length == 0 ) {	
			jQuery('#text_description').trigger('focusout');
			jQuery('#text_title').trigger('focusout');


			var l_oTitle = jQuery(this);
			var l_sTitle = l_oTitle.text();
			var l_nTaskId = jQuery(this).parent().parent().data('task_id');

			jQuery(this).html( '<input id="text_title" name="title" type="text">');
			jQuery('#text_title')
				.val( l_sTitle )
				.css('width', '80%')
				.focus()
				.focusout(function() {
					l_oTitle.text( l_sTitle );
				}) ;

			jQuery('#text_title').keydown(function(e) {
				if( e.keyCode == 27 ) {
					// ESC == cancel
					l_oTitle.text( l_sTitle );
					e.preventDefault();
				} else if ( e.keyCode == 13 && !e.shiftKey) {
					e.preventDefault();
					// ENTER - submit
					var textField = this;
					jQuery.ajax({
						type: 'POST',
						url: gAjaxUrl,
						dataType: 'json',
						data : {
							action   : 'update',
							group_id : gGroupId,
							task_id : l_nTaskId,
							title : jQuery(this).val()
						},
						async: true
					}).done(function( answer ) {
						if(answer['message']) {
							showMessage(answer['message'], 'error');
						}

						if(answer['action'] == 'reload') {
							// reload whole board
							loadTaskboard( gGroupId );
						}

						l_oTitle.text( jQuery(textField).val() );
					}).fail(function( jqxhr, textStatus, error ) {
						var err = textStatus + ', ' + error;
						alert(err);
					});
				}
			});
		}
	});

	// description
	jQuery("div.agile-sticker-body").dblclick( function () {
		if( jQuery(this).children('textarea').length == 0 ) {
			// close open textarea
			jQuery('#text_description').trigger('focusout');
			jQuery('#text_title').trigger('focusout');


			var l_oDesc = jQuery(this);
			var l_sDescription = l_oDesc.html();
			var l_nTaskId = jQuery(this).parent().data('task_id');
			jQuery(this).html( '<textarea id="text_description" name="description" rows="11"></textarea>');

			
			jQuery('#text_description')
				.html( l_sDescription.replace(/<br>/g, "\n") )
				.css('width', '98%')
				.css('height', '95%')
				.focus()
				.focusout(function() {
					l_oDesc.html( l_sDescription );
				}) ;
			jQuery('#text_description').keydown(function(e) {
				if( e.keyCode == 27 ) {
					// ESC == cancel
					l_oDesc.html( l_sDescription );
					e.preventDefault();
				} else if ( e.keyCode == 13 && !e.shiftKey) {
					e.preventDefault();
					// ENTER - submit
					var textField = this;
					jQuery.ajax({
						type: 'POST',
						url: gAjaxUrl,
						dataType: 'json',
						data : {
							action   : 'update',
							group_id : gGroupId,
							task_id : l_nTaskId,
							desc : jQuery(this).val()
						},
						async: true 
					}).done(function( answer ) {
						if(answer['message']) {
							showMessage(answer['message'], 'error');
						}

						if(answer['action'] == 'reload') {
							// reload whole board
							loadTaskboard( gGroupId );
						}

						l_oDesc.html(jQuery(textField).val().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>') );
					}).fail(function( jqxhr, textStatus, error ) {
						var err = textStatus + ', ' + error;
						alert(err);
					});
				}
			});
		}
	});	
	
}
