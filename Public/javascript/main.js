(function() {
  Tracker.User.getID = function() {
    if (!Tracker.User.data || !Tracker.User.data.id) {
      return false;
    }
    
    return Tracker.User.data.id;
  };
  
  Tracker.User.getName = function() {
    if (!Tracker.User.data || !Tracker.User.data.name) {
      return false;
    }
    
    return Tracker.User.data.name;
  };
}());

(function() {
  var conditionCount = 0,
      dropdowns = {
        fields: $('<select><optgroup label="Ticket"><option value="title">Title</option><option value="assignee">Assignee</option><option value="type">Type</option><option value="state">State</option><option value="encoding_profile">Encoding profile</option></optgroup><optgroup label="Properties"><option value="fahrplan_id">Fahrplan ID</option><option value="date">Date</option><option value="time">Time</option><option value="room">Room</option></optgroup><optgroup label="Other"><option value="modified">Modified</option></optgroup></select>'),
        operators: {
          basic: $('<select><option value="is">is</option><option value="is_not">is not</option></select>'),
          multiple: $('<select><option value="is">is</option><option value="is_not">is not</option><option value="is_in">is in</option><option value="is_not_in">is not in</option></select>'),
          text: $('<select><option value="contains">contains</option><option value="begins_with">begins with</option><option value="ends_with">ends with</option><option value="is">is</option></select>')
        },
        states: $('#tickets-search-states'),
        types: $('#tickets-search-types'),
        rooms: $('<select></select>'),
        assignees: $('#tickets-search-assignees'),
        profiles: $('#tickets-search-profiles')
      };
  
  function addCondition(event, postField, postOperator, postValue) {
    if (event) {
      event.preventDefault();
    }
    
    var li = $('<li></li>'),
      field,
      operator,
      value,
      extra;
    conditionCount++;
    
    if (event && event.target) {
      li.insertAfter($(event.target).parent());
    } else {
      li.insertBefore('#tickets-search-conditions li:last-child');
    }
    
    dropdowns.fields.clone().change(function(event) {
      field = event.target.options[event.target.selectedIndex].value;
      
      if (operator) {
        operator.remove();
        operator = null;
      }
      
      if (extra) {
        extra.remove();
        extra = null;
      }
      
      if (value) {
        value.remove();
        value = null;
      }
      
      switch (field) {
        case 'title':
          operator = dropdowns.operators.text.clone();
          break;
        case 'fahrplan_id':
          operator = dropdowns.operators.multiple.clone();
          break;
        default:
          operator = dropdowns.operators.basic.clone();
          break;
      }
      
      operator.attr('name', 'operators[' + conditionCount + ']').change(function(event) {
        if (!value) {
          switch (field) {
            case 'state':
              value = dropdowns.states.clone();
              break;
            case 'type':
              value = dropdowns.types.clone();
              break;
            case 'assignee':
              value = dropdowns.assignees.clone();
              break;
            case 'encoding_profile':
              value = dropdowns.profiles.clone();
              break;
            default:
              value = $('<input></input>').addClass('text');
          }

          value.attr('name', 'values[' + conditionCount + ']').insertAfter(event.target);
        }
        
        if (postValue) {
          value.attr('value', postValue);
        }
        
        if (extra) {
          extra.remove();
          extra = null;
        }
        
        switch (field) {
          case 'fahrplan_id':
            switch (event.target.options[event.target.selectedIndex].value) {
              case 'is_in':
              case 'is_not_in':                    
                extra = $('<span class="description">Separate multiple entries by comma</span>').insertAfter(value);
                value.addClass('wide');
                break;
              default:
                value.removeClass('wide');
            }
            break;
        }
      }).insertAfter(event.target);
      
      if (postOperator) {
        operator.attr('value', postOperator);
      }
      
      operator.trigger('change');
    }).appendTo(li).attr('name','fields[' + conditionCount + ']').attr('value', (postField)? postField : null).trigger('change');
    
    if (conditionCount > 1) {
      $('<a></a>').attr('href', '#').addClass('tickets-search-remove').click(function(event) {
        event.preventDefault();
        li.remove();
        conditionCount--;
      }).appendTo(li);
    }
    $('<a></a>').attr('href', '#').addClass('tickets-search-add').click(addCondition).appendTo(li);
  }
  
  Tracker.Search.init = function() {
    if (search && (search.fields && search.operators && search.values)) {
      $.each(search.fields, function(i, field) {
        addCondition(null, field, search.operators[i], search.values[i]);
      });
    } else {
      addCondition();
    }
    
    /*
    $(document).keydown(function(event) {
      if (event.altKey) { // keyCode == 18
        $('.tickets-search-add').addClass('more');
      }
    });
    
    $(document).keyup(function(event) {
      if (event.keyCode == 18) {
        $('.tickets-search-add').removeClass('more');
      }
    });
    */
  };
}());

(function() {
  var assignee = {
        select: null,
        description: null
      },
      comment = {
        attachedTo: null,
        field: $('<li><label for="ticket-edit-comment">Comment</label><textarea class="wide" id="ticket-edit-comment" name="comment"></textarea><span class="description"></span></li>')
      },
      failed = {
        checkbox: null
      },
      needsAttention = {
        checkbox: null
      },
      set = {
        failed: null,
        needs_attention: null
      },
      state = {
        description: null,
        encodingProfile: null,
        initial: null,
        next: null,
        select: null,
        showEncodingProfile: false
      };
  
  function removeAssignee(event) {
    event.preventDefault();
    assignee.select[0].options[0].selected = true;
  }
  
  function assignToUser(event) {
    event.preventDefault();

    for (var i = 0, id = Tracker.User.getID(); i < assignee.select[0].options.length; i++) {
      if (assignee.select[0].options[i].value == id) {
        assignee.select[0].options[i].selected = true;
        break;
      }
    }
  }
  
  function updateState() {
    if (state.select[0].selectedIndex != state.initial) {      
      failed.checkbox[0].checked = false;
      failed.checkbox.trigger('change');
    } 

    if ((state.select[0].selectedIndex + 1) < state.select[0].options.length) {
      state.next.text('Select next state (' + state.select[0].options[state.select[0].selectedIndex+1].text + ')');
    } else {
      state.next.text('Select first state (' + state.select[0].options[0].text + ')');
    }
    
    if (state.showEncodingProfile) {
      if ($('#ticket-edit-state option[value="' + state.select[0].options[state.select[0].selectedIndex + 1].value + '"]').parent().attr('label') == 'encoding task') {
        state.encodingProfile.show();
      } else {
        state.encodingProfile.hide();
      }
    }
  }
  
  function nextState(event) {
    event.preventDefault();

    if ((state.select[0].selectedIndex + 1) < state.select[0].options.length) {
      state.select[0].options[state.select[0].selectedIndex+1].selected = true;
    } else {
      state.select[0].options[0].selected = true;
    }
    
    updateState();
  }
  
  function resetState(event) {
    event.preventDefault();

    state.select[0].options[state.initial].selected = true;
    updateState();
    
    failed.checkbox[0].checked = failed.initial;
    
    if (!failed.initial) {
      failed.checkbox.trigger('change');
    }
  }
  
  function updateCommentField(event) {
    if (event.target.checked) {
      if (!comment.attachedTo) {
        comment.field.insertAfter($(event.target).parent()).find('span').text('Describe why ' + ((event.target.name == 'failed')? 'the current state failed.' : 'this ticket needs attention.'));
        comment.attachedTo = event.target.name;
      }
      
      $('#ticket-edit-comment').focus();
    } else if (event.target.name == comment.attachedTo) {
      comment.field.detach();
      comment.attachedTo = null;
      
      if (event.target.name == 'failed') {
        needsAttention.checkbox.trigger('change');
      } else {
        failed.checkbox.trigger('change');
      }
    }
  }
  
  function updateIndeterminate(event) {
    if (!set[event.target.name][0].disabled && !event.target.checked) {
      event.target.indeterminate = true;
    }
    
    set[event.target.name][0].disabled = event.target.indeterminate;
  }
  
  // TODO: implement stale ticket tracking and display
  Tracker.Edit = {
    init: function() {
      if ($('#ticket-edit').hasClass('mass')) {
        return Tracker.Edit.Mass.init();
      }
      
      assignee.select = $('#ticket-edit-handle_id');
      assignee.description = $('<span> or </span>').addClass('description').insertAfter(assignee.select);
      
      $('<a></a>').attr('href', '#').text('Remove assignee').click(removeAssignee).prependTo(assignee.description);
      $('<a></a>').attr('href', '#').text('assign to me (' + Tracker.User.getName() + ')').click(assignToUser).appendTo(assignee.description);
      
      failed.checkbox = $('#ticket-edit-failed').change(updateCommentField);;
      failed.initial = failed.checkbox[0].checked;
      
      needsAttention.checkbox = $('#ticket-edit-needs_attention').change(updateCommentField);
      
      state.showEncodingProfile = !!$('#ticket-edit-state optgroup')[0];
      state.select = $('#ticket-edit-state').change(updateState);
      state.initial = state.select[0].selectedIndex;
      
      // TODO: add class "disable" if ticket is in intial state
      state.description = $('<span> or </span>').addClass('description').insertAfter(state.select);
      state.next = $('<a></a>').attr('href', '#').click(nextState).prependTo(state.description);
      $('<a></a>').attr('href', '#').text('reset to initial').click(resetState).appendTo(state.description);
      
      state.encodingProfile = $('#ticket-edit-encoding_profile').parent();
      
      updateState();
      
      $('fieldset.foldable').addClass('folded')
        .find('legend').append(
          $('<a></a>').attr({'href': '#', 'title': 'Expand section'}).text('expand').click(function(event) {
            event.preventDefault();
            
            event.target = $(event.target);
            var section = event.target.parent().parent();
            
            if (section.hasClass('folded')) {
              event.target.attr('title', 'Fold section').text('fold');
              section.removeClass('folded').addClass('expanded');
            } else {
              event.target.attr('title', 'Expand section').text('expand');
              section.removeClass('expanded').addClass('folded');
            }
          })
        );
    },
    
    Mass: {
      init: function() {
        failed.checkbox = $('#ticket-edit-failed').click(updateIndeterminate);
        needsAttention.checkbox = $('#ticket-edit-needs_attention').click(updateIndeterminate);
        
        set.failed = $('#ticket-edit-set_failed');
        set.needs_attention = $('#ticket-edit-set_needs_attention');
        
        failed.checkbox[0].indeterminate = true;
        needsAttention.checkbox[0].indeterminate = true;
      }
    }
  };
}());

(function() {
  var content = null,
      tickets = null,
      url = null,
      currentIndex = null;
  
  function getTickets(data, pushState, animationIndex, onsuccess) {
    tickets.css({'left': 'auto', 'right': 'auto'});
    
    if (animationIndex > currentIndex) {
      tickets.animate({'left': '-' + (content.width() + 10) + 'px'}, 200);
    } else if (animationIndex < currentIndex) {
      tickets.animate({'right': '-' + (content.width() + 10) + 'px'}, 200);
    }
    
    if (animationIndex != currentIndex) {
      tickets.queue(function(dequeue) {
        content.addClass('wait');
        dequeue();
      });
    }
    
    // TODO: try again on error, than show message
    $.ajax({
      url: url + '.json',
      type: 'GET',
      dataType: 'json',
      data: data,
      success: function(result, status, xhr) {
        if (result) {
          tickets.empty().clearQueue();
          
          hideFlashMessage();
          
          if (animationIndex > currentIndex) {
            tickets.css({'left': content.width() + 10});
          } else if (animationIndex < currentIndex) {
            tickets.css({'right': content.width() + 10});
          }
          
          tickets.html(result.join(''));
          
          if (animationIndex > currentIndex) {
            tickets.animate({'left': 0}, 200);
          } else if (animationIndex < currentIndex) {
            tickets.animate({'right': 0}, 200);
          }
          
          currentIndex = animationIndex;
          content.removeClass('wait');
          
          if (pushState) {
            history.pushState({controller: 'tickets', action: 'view', data: data, ai: animationIndex}, 'Tickets', url + ((data)? '?' + $.param(data) : ''));
          }
          
          updateLinks();
          
          if (onsuccess) {
            onsuccess.call(null);
          }
        }
      }
    });
  }
  
  function updateLinks() {
    tickets.find('a[data-handle]').each(function(i, link) {
      $(link).click(function(event) {
        if(!event.metaKey && !event.controlKey) {
          event.preventDefault();
          
          getTickets({'u': $(link).data('handle')}, true, 0);
          
          $('.ticket-header-bar li.current').removeClass('current');
          $('.ticket-header-bar li.first').addClass('current');
          
          $('#tickets-search').hide();
        }
      });
    });
  }
  
  function hideFlashMessage() {
    if ($('#flash')[0]) {
      $('#flash').animate({'margin-top': '-2.35em'}, 350).queue(function(dequeue) {
        $(this).hide();
        dequeue();
      });
    }
  }
  
  Tracker.Tickets = {
    init: function() {
      if (!history.pushState) {
        return false;
      }
      
      content = $('#content');
      tickets = $('ul.tickets');
      
      if (!tickets[0]) {
        tickets = $('<ul class="tickets"></ul>').insertAfter($('#tickets-header'));
      }
      
      url = $('#tickets-filter')[0].action;
      currentIndex = $('.ticket-header-bar li.current').data('ai');
      
      if (!currentIndex) {
        currentIndex = 1;
      }
      
      $('#tickets-header .ticket-header-bar button').each(function(i, button) {
        if (button.id != 'tickets-filter-search') {
          $(button).click(function(event) {
            if (!event.metaKey && !event.controlKey) {
              event.preventDefault();
              
              getTickets((button.value)? {t: button.value} : null, true, $(button).parent().data('ai'));
              
              $('.ticket-header-bar li.current').removeClass('current');
              $(button).parent().addClass('current');
              
              $('#tickets-search').hide();
            }
          });
        }
      });
      
      updateLinks();
      
      $(window).bind('popstate', function(event) {
        if (event.originalEvent.state) {
          if (event.originalEvent.state.controller && event.originalEvent.state.controller == 'tickets' && event.originalEvent.state.action == 'view') {
            getTickets(event.originalEvent.state.data, false, event.originalEvent.state.ai);
          }
        } else {
          // TODO: parse URL
        }
      });
    }
  };
}());

(function() {
  Tracker.Ticket = {
    init: function() {
      $('span.more').each(function(i, span) {
        $(span).hide();
        
        $('<a></a>').attr({'href': '#', 'title': 'Show more…'}).text('more').insertAfter(span).click(function(event) {
          event.preventDefault();
          
          $(span).show();
          $(event.target).remove();
        });
      });
      
      
    }
  };
}());

(function() {
  var comment = null,
      failed = null,
      reset = null,
      submit = {
        button: null,
        originalText: ''
      };
  
  Tracker.Action = {
    init: function() {
      comment = $('#ticket-action-comment');
      expandLeft = $('#ticket-action-expand_left').parent().detach();
      expandRight = $('#ticket-action-expand_right').parent().detach();
      delayBy = $('#ticket-action-delay_by').parent();
      
      failed = $('#ticket-action-failed');
      expand = $('#ticket-action-expand');
      delay = $('#ticket-action-delay');
      reset = $('#ticket-action-reset');
      
      submit.button = $('#ticket-action input[type="submit"]')[0];
      submit.originalText = submit.button.value;
      
      if (expand[0]) {
        expand.change(function(event) {
          if (event.target.checked) {
            if (failed[0].checked) {
              failed[0].checked = false;
              failed.trigger('change');
            }
            
            expandLeft.insertAfter(expand.parent());
            expandRight.insertAfter(expandLeft);
            
            submit.button.value = 'Expand timeline and set up preparing';
          } else {
            expandLeft.detach();
            expandRight.detach();
            
            submit.button.value = submit.originalText;
          }
        });
      }
      
      if (delay[0]) {
        if (!delay[0].checked) {
          delayBy.detach();
        }
        
        delay.change(function(event) {
          if (event.target.checked) {
            delayBy.insertAfter(delay.parent());
          } else {
            delayBy.detach();
          }
        });
      }
      
      if (reset[0]) {
        reset.change(function(event) {
          if (event.target.checked) {
            if (failed[0].checked) {
              failed[0].checked = false;
            }
            
            comment.parent().detach().insertAfter(reset.parent()).show();
            comment.focus();
            
            submit.button.value = 'Reset encoding tickets and mark parent as failed';
          } else {
            comment.parent().hide().detach().insertAfter(failed.parent());
            submit.button.value = submit.originalText;
          }
        });
      }
      
      failed.change(function(event) {
        if (event.target.checked) {
          if (reset[0] && reset[0].checked) {
            reset[0].checked = false;
            reset.trigger('change');
          }
          
          if (expand[0] && expand[0].checked) {
            expand[0].checked = false;
            expand.trigger('change');
          }
          
          comment.parent().show();
          comment.focus();

          if (failed.hasClass('wontfix')) {
            submit.button.value = 'Mark ticket as wontfix';
          } else {
            submit.button.value = 'Mark ticket as failed';
          }
        } else {
          comment.parent().hide();
          submit.button.value = submit.originalText;
        }
      });
    }
  };
}());

(function() {
  var actions = null,
      feed = null,
      latestEntries = [],
      next = null,
      previous = null,
      progress = null,
	    progressBar = null,
      timeout = null;
  
  function poll() {
    var first = feed.find('li.event:first'),
        id = first.data('id');
    
    if (id) {
      $.ajax({
        url: window.location.pathname + '.json',
        type: 'GET',
        dataType: 'json',
        data: {'after': id},
        success: function(result, status, xhr) {
          if (!result || !result.entries) {
            return;
          }
          
          if (result.entries.length > 0) {
            latestEntries = result.entries;
            
            if (!next) {
              next = $('<a></a>').attr('href', '#').click(function(event) {
                next.parent().hide();
                
                $.each(latestEntries, function(i, entry) {
                  $(entry).insertBefore(first);
                });
                
                latestResult = [];
                next.parent().remove();
                next = null;
                updateTitle();
                
                event.preventDefault();
              }).hide().appendTo($('<li></li>').addClass('more').prependTo(feed)).fadeIn(700);
            }
            
            next.text('Show ' + result.entries.length + ' new entr' + ((result.entries.length == 1)? 'y' : 'ies'));
            updateTitle(result.entries.length);
          }
          
          if (result.actions) {
            actions = $(result.actions).replaceAll(actions);
          }
          
          if (result.progress) {
            progressBar = $(result.progress).replaceAll(progressBar);
          }
          
          timeout = setTimeout(poll, 15000);
        }
      });
    }
  }
  
  function updateTitle(newEntries) {
    if (!newEntries) {
      document.title = document.title.replace(/Feed \([0-9]+\)/, 'Feed');
      return;
    }
    
    document.title = document.title.replace(/Feed( \([0-9]+\))?/, 'Feed (' + latestEntries.length + ')');
  }
  
  function updatePolling() {
    var hidden = false;
    
    if (typeof document.hidden !== 'undefined') {
      hidden = document.hidden;
    } else if (typeof document.webkitHidden !== 'undefined') {
      hidden = document.webkitHidden;
    }
    
    if (hidden) {
      clearTimeout(timeout);
    } else {
      poll();
    }
  }
  
  Tracker.Feed = {
    init: function() {
      feed = $('#feed');
      progress = $('<img>').attr({'src': 'images/progress.gif', 'alt': 'Loading…', 'title': 'Loading…'});
      
      previous = $('<a></a>').attr('href', '#').text('Show older entries').click(function(event) {
        previous.hide();
        progress.insertAfter(previous);
        
        var id = feed.find('li.event:last').data('id'),
            last = feed.find('li.more');
        
        if (id) {
          $.ajax({
            url: window.location.pathname + '.json',
            type: 'GET',
            dataType: 'json',
            data: {'before': id},
            success: function(result, status, xhr) {
              if (!result || !result.entries) {
                return;
              }
              
              if (result.entries.length == 0) {
                $(event.target).parent().hide();
              }
              
              $.each(result.entries, function(i, entry) {
                $(entry).insertBefore(last);
              });
              
              progress.detach();
              previous.show();
            }
          });
        }
        
        event.preventDefault();
      }).appendTo($('<li></li>').addClass('more').appendTo(feed));
      
      actions = $('#feed-stats-actions');
      progressBar = $('#feed-stats-progress-bar');
	  
      timeout = setTimeout(poll, 15000);
      
      $(document).bind('webkitvisibilitychange', updatePolling);
      $(document).bind('visibilitychange', updatePolling);
    }
  };
}());

(function() {
  function appendDeleteButton(input) {
    var deleteField,
        deleteName = input.data('property-destroy');
    
    $('<a></a>')
      .attr('href', '#')
      .addClass('delete')
      .text('Delete ' + this.description)
      .attr('title', 'Delete ' + this.description)
      .click(function(event) {  
        event.preventDefault();
        
        if (input[0].disabled) {
          input[0].disabled = false;
          
          $(event.target)
            .removeClass('restore')
            .text('Delete ' + this.description)
            .attr('title', 'Delete ' + this.description);
          
            deleteField.detach();
        } else {
          input[0].disabled = true;
          $(event.target)
            .addClass('restore')
            .text('Restore ' + this.description)
            .attr('title', 'Restore ' + this.description);
          
          if (!deleteField) {
            deleteField = $('<input>')
              .attr({
                'type': 'hidden',
                'name': deleteName,
                'value': 1
              });
          }
          
          deleteField.insertAfter(input);
        }
      }.bind(this))
      .insertAfter(input);
  }
  
  function appendCreateButton() {
    this.createButton = $('<a></a>')
      .attr('href', '#')
      .text('Add new ' + this.description)
      .click(function(event){
        event.preventDefault();
        
        var container = $('<li></li>').insertBefore(this.insertBefore),
            lastInput = this.list.find('input[data-property-index]:last'),
            lastIndex = (lastInput[0])? lastInput.data('property-index') : -1;
            
        $('<input></input>')
          .attr({
            'type': 'text',
            'name': this.create.key.replace('[]', '[' + (lastIndex + 1) + ']'),
            'class': 'text'
          })
          .appendTo($('<label></label>').appendTo(container))
          .focus();
        
        $('<input></input>')
          .attr({
            'type': 'text',
            'name': this.create.value.replace('[]', '[' + (lastIndex + 1) + ']'),
            'class': 'text',
            'data-property-index': lastIndex + 1
          })
          .appendTo(container);
        
        $('<a></a>')
          .attr('href', '#')
          .addClass('delete')
          .text('Delete ' + this.description)
          .click(function(event) {
            event.preventDefault();
            container.remove();
          })
          .appendTo(container);
      }.bind(this))
      .appendTo($('<p></p>').appendTo($('<li><label></label></li>').appendTo(this.list)));
    
    this.insertBefore = this.createButton.closest('li');
  }
  
  Tracker.Properties = function(list) {
    this.list = $(list);
    this.description = this.list.data('properties-description') || 'property';
    this.create = {
      key: this.list.data('properties-create-key') || 'key',
      value: this.list.data('properties-create-value') || 'value'
    };
    
    this.list.find('li').each(function(i, li) {
      appendDeleteButton.call(this, $(li).children('input.text'));;
    }.bind(this));
    
    // TODO: read data-label
    
    appendCreateButton.call(this);
  };
})();

$(function() {
  $('#ticket-action-comment.hidden').parent().hide();
  // $('#ticket-action-expand_by').parent().hide();
  
  if ($('#ticket-edit-state optgroup')[0]) {
    $('#ticket-edit-encoding_profile').parent().hide();
  }
  
  if ($('#tickets-search')[0]) {
    Tracker.Search.init();
  }
  
  if ($('#ticket-edit')[0]) {
    Tracker.Edit.init();
  }
  
  if ($('#timeline')[0]) {
    Tracker.Ticket.init();
  }
  
  if ($('#tickets-filter')[0]) {
    Tracker.Tickets.init();
  }
  
  if ($('#feed')[0]) {
    Tracker.Feed.init();
  }
  
  $('ul.edit-properties').each(function(i, ul) {
    new Tracker.Properties(ul);
  });
  
  $('#ticket-import-file').change(function(event) {
    if (event.target.value) {
      $('#ticket-import-url')[0].disabled = true;
    } else {
      $('#ticket-import-url')[0].disabled = false;
    }
  });
  
  if ($('#ticket-action')[0]) {
    Tracker.Action.init();
  }
  
  if ($('#ticket-import-list')[0]) {
    $('<a></a>').attr('href', '#').text('Invert selection').click(function(event) {
      event.preventDefault();
      $('#ticket-import-list input.checkbox').each(function(i, checkbox) {
        checkbox.checked = !checkbox.checked;
      });
    }).appendTo($('#ticket-import-list fieldset:last ul li'));
  }
  
  $('ul[data-invert-checkboxes]').each(function(i, ul) {
    ul = $(ul);
    
    $('<a></a>')
      .attr('href', '#')
      .text('Invert selection')
      .click(function(event) {
        event.preventDefault();
        ul.find('input.checkbox').each(function(i, checkbox) {
          checkbox.checked = !checkbox.checked;
        });
      }).insertAfter($(ul).find('input.submit:last'));
  });
  
  $('#encoding-profile-versions tr.encoding-profile-version')
    .hide()
    .each(function(i, tr) {
      tr = $(tr);
      
      tr
        .prevUntil('tr.encoding-profile-version')
        .find('.encoding-profile-version-show')
        .append(
          $('<a>')
            .attr({
              'href': '#',
              'title': 'Show xml template'
            })
            .text('Show xml template')
            .click(function(event) {
              event.preventDefault();
              
              var target = $(event.target);
              
              if (tr.is(':hidden')) {
                tr.show();
                target
                  .text('Hide xml template')
                  .attr('title', 'Hide xml template');
                tr.find('textarea').data('editor').refresh();
              } else {
                tr.hide();
                target
                  .text('Show xml template')
                  .attr('title', 'Show xml template');
              }
            })
        );
    });
  
  $('textarea[data-has-editor]').each(function(i, textarea) {
    $(textarea).data('editor', CodeMirror.fromTextArea(textarea, {
      lineNumbers: true,
      // may affect performance but is needed to fit content
      viewportMargin: Infinity,
      readOnly: textarea.readOnly,
      autoCloseTags: true
    }));
  });
  
  $('select[data-submit-on-change], input[type="checkbox"][data-submit-on-change]')
    .each(function(i, field) { // TODO: remove each
      $(field).change(function(event) {
        event.target.form.submit();
      });
    });
  
  $('select[data-encoding-profile-version-id]').change(function(event) {
    var target = $(event.target),
        lastIndex = parseInt($('select[data-encoding-profile-index]')
          .last()
          .data('encoding-profile-index'), 10);
    
          // TODO: set name via data-*?
    $('<input>')
      .attr({
        'type': 'hidden',
        'name': 'EncodingProfileVersion[' + (lastIndex + 1) + '][encoding_profile_version_id]',
        'value': target.data('encoding-profile-version-id')
      })
      .insertAfter(target);
    $('<input>')
      .attr({
        'type': 'hidden',
        'name': 'EncodingProfileVersion[' + (lastIndex + 1) + '][_destroy]',
        'value': 1
      })
      .insertAfter(target);
    
      event.target.form.submit();
  });
  
  $('input[data-encoding-profile-destroy]').each(function(i, input) {
    input = $(input);
    
    input.hide();
    
    $('<a>')
      .attr({
        'href': '#'
      })
      .text('remove')
      .insertAfter(input)
      .click(function(event) {
        event.preventDefault();
        
        input.click();
      });
    
    input.parent('td').addClass('link');  
  });
  
  $('input[data-worker-group-destroy]')
    .change(function(event) {
      var target = $(event.target);
    
      if (!event.target.checked) {
        target.data(
          'destroy',
          $('<input>')
            .attr({
              'type': 'hidden',
              'name': target.data('worker-group-destroy'),
              'value': 1
            })
            .insertAfter(target)
        );
      } else if (target.data('destroy')) {
        target.data('destroy').remove();
      }
    })
    .each(function(i, input) {
      $('<input>')
        .attr({
          'type': 'hidden',
          'name': input.name,
          'value': input.value
        })
        .insertAfter(input);
    });
  
  var projectBarHidden = $.cookie('p') == '0',
      basePath = /https?\:\/\/(.*?)(\/.*)/.exec($('base')[0].href);
  
  $('<a></a>').attr({href: '#', id: 'projects-hide'}).appendTo($('#projects'))
    .text((projectBarHidden)? 'Show project bar' : 'Hide project bar').click(function(event) {
      event.preventDefault();
      
      if (!projectBarHidden) {
        $('#projects ul').animate({'margin-top': '-32'}, 400, function() {
          $('#projects ul').css('margin-top', '0px').hide();
          $(event.target).text('Show project bar');
        });
        $('body').addClass('full');
        $.cookie('p', 0, {path: basePath[2]});
        projectBarHidden = true;
      } else {
        $('#projects ul').css('margin-top', '-32px').show().animate({'margin-top': '0'}, 400, function() {
          $('body').removeClass('full');
          $(event.target).text('Hide project bar');
        });
        $.cookie('p', 1, {path: basePath[2]});
        projectBarHidden = false;
      }
    }).css('opacity', 0).hover(function(event) {
      $(event.target).animate({opacity: 1}, 300);
    }, function(event) {
      $(event.target).clearQueue().animate({opacity: 0}, 300);
    });
  
  $('#user-login-user').focus();
  
  $('a[data-dialog-confirm]').each(function(i, a) {
    $(a).click(function(event) {
      return confirm($(a).data('dialog-confirm'));
    });
  });
  
  // TODO: convert
  $('a.confirm-ticket-delete').click(function(event) {
    return confirm('Are you sure you want to permanently delete this ticket?');
  });
  
  $('a.confirm-user-delete').click(function(event) {
    return confirm('Are you sure you want to permanently delete this user?');
  });
  
  $('a.confirm-user-unregister').click(function(event) {
    return confirm('Are you sure you want to unregister this worker?');
  });
  
  $('a.confirm-ticket-reset').click(function(event) {
    return confirm('Are you sure you want to reset this encoding task?');
  });
});