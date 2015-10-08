"use strict";

var Tracker = {};

/*
  
  Search
  
*/
(function() {
  var conditionCount = -1,
      dropdowns = {
        fields: $('<select>\
          <optgroup label="Ticket">\
            <option value="title">Title</option>\
            <option value="assignee">Assignee</option>\
            <option value="type">Type</option>\
            <option value="state">State</option>\
            <option value="failed">State failed</option>\
            <option value="encoding_profile">Encoding profile</option>\
          </optgroup>\
          <optgroup label="Properties">\
            <option value="fahrplan_id">Fahrplan ID</option>\
            <option value="room">Room</option>\
            <option value="day">Day</option>\
            <option value="optout">Opt-Out</option>\
          </optgroup>\
          <optgroup label="Other">\
            <option value="modified">Modified</option>\
          </optgroup>\
        </select>'),
        operators: {
          basic: $('<select>\
            <option value="is" selected>is</option>\
            <option value="is_not">is not</option>\
          </select>'),
          multiple: $('<select>\
            <option value="is" selected>is</option>\
            <option value="is_not">is not</option>\
            <option value="is_in">is in</option>\
            <option value="is_not_in">is not in</option>\
          </select>'),
          text: $('<select>\
            <option value="contains" selected>contains</option>\
            <option value="begins_with">begins with</option>\
            <option value="ends_with">ends with</option>\
            <option value="is">is</option>\
          </select>')
        },
        states: $('#tickets-search-states'),
        bool: $('<select><option value="1">true</option><option value="0">false</option></select>'),
        types: $('#tickets-search-types'),
        rooms: $('#tickets-search-rooms'),
        days: $('#tickets-search-days'),
        assignees: $('#tickets-search-assignees'),
        profiles: $('#tickets-search-profiles')
      },
      quicksearch = {
        forbiddenCharacters: /[^\w\u0080-\u00FF\u0100-\u017F\u0180-\u024F\-\_\: ]/g,
        lastQ: null,
        numbers: /^[0-9]{4,}$/,
        q: null,
        tickets: null,
        timer: null
      },
      ticketEditButton = null,
      ticketEditSelect = null,
      tickets = null;
  
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
    
    dropdowns.fields.clone().change(function(event, field) {
      field = field || event.target.options[event.target.selectedIndex].value;
      
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
            case 'room':
              value = dropdowns.rooms.clone();
              break;
            case 'day':
              value = dropdowns.days.clone();
              break;
            case 'failed':
            case 'optout':
              value = dropdowns.bool.clone();
              break;
            default:
              value = $('<input/>').addClass('text');
              break;
          }

          value
            .removeAttr('id')
            .attr('name', 'values[' + conditionCount + ']')
            .insertAfter(event.target);
          
          if (postField == field && postValue) {
            value.val(postValue);
          }
        }
        
        if (extra) {
          extra.remove();
          extra = null;
        }
        
        switch (field) {
          case 'fahrplan_id':
            switch (this.value) {
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
      
      if (postField == field && postOperator) {
        operator.val(postOperator);
      }
      
      operator.trigger('change');
    }).appendTo(li).attr('name','fields[' + conditionCount + ']').val(postField || 'title').trigger('change', [postField]);
    
    $('<a></a>').attr('href', '#').addClass('tickets-search-remove').click(function(event) {
      event.preventDefault();
      li.remove();
      conditionCount--;
      $('#tickets-search-conditions').toggleClass('single-filter', conditionCount <= 0);
    }).appendTo(li);
    
    $('#tickets-search-conditions').toggleClass('single-filter', conditionCount <= 0);
    $('<a></a>').attr('href', '#').addClass('tickets-search-add').click(addCondition).appendTo(li);
  }
  
  function clearSearch() {
    quicksearch.tickets.find('li').show();
    quicksearch.lastQ = '';
  }
  
  function doSearch(q) {
    if (quicksearch.lastQ !== null && quicksearch.lastQ === q) {
      return;
    }
    
    if (q === '') {
      clearSearch();
      return;
    }
    
    var isFahrplanId = quicksearch.numbers.test(q),
        regexp = new RegExp(((isFahrplanId)? '^' : '') +
          q.replace(quicksearch.forbiddenCharacters, ''), 'i');
    
    quicksearch.tickets.find('li:not(.child)').each(function(i, ticket) {
      ticket = $(ticket);
      
      var tickets = quicksearch.tickets
            .find('li[data-fahrplan-id=' + ticket.data('fahrplan-id') + ']'),
          match = false;
      
      if (isFahrplanId) {
        match = regexp.test(ticket.data('fahrplan-id'));
      } else {
        match = regexp.test(ticket.data('title'));
      }
      
      if (match) {
        tickets.show();
      } else {
        tickets.hide();
      }
    });
    
    quicksearch.lastQ = q;
  }
  
  function uncheckNotOfType(type) {
    tickets
      .filter('[data-ticket-type!="' + type + '"]')
      .find('input.ticket-search-edit-select')
      .prop('checked', false);
  }
  
  function updateEditButton() {
    ticketEditButton
      .prop(
        'disabled',
        tickets.find('input.ticket-search-edit-select:checked').length <= 0
      );
  }
  
  function initTicketEdit() {
    tickets = $('ul.tickets li')
      .prepend(
        $('<input></input>')
          .attr({
            'type': 'checkbox',
            'class': 'ticket-search-edit-select'
          })
          .change(function(event) {
            ticketEditSelect.val('');
            
            if (!event.target.checked) {
              updateEditButton();
              return;
            }
            
            uncheckNotOfType($(event.target).parent().data('ticket-type'));
            updateEditButton();
          })
      );
    
    ticketEditButton = $('<input></input>')
      .attr({
        'type': 'button',
        'value': 'Edit selected'
      })
      .prop('disabled', true)
      .click(function(event) {
        var url = $('#tickets-search')
          .data('edit-url')
          .replace(
            '{tickets}',
            tickets
              .find('input.ticket-search-edit-select:visible:checked')
              .parents()
              .map(function() {
                return $(this).data('id');
              })
              .get()
              .join(',')
          );
        
        if (event.metaKey) {
          window.open(url, '_blank');
        } else {
          window.location.href = url;
        }
      });
    
    ticketEditSelect = dropdowns.types
      .clone();
    
    ticketEditSelect
      .find('option')
      .each(function() {
        if (tickets.filter('[data-ticket-type="' + this.value + '"]').length <= 0) {
          this.disabled = true;
        }
      });
    
    ticketEditSelect
      .prepend($('<option value="">-</option>'))
      .val('')
      .prop('disabled', tickets.length <= 0)
      .change(function(event) {
        if (event.target.value) {
          tickets
            .filter('[data-ticket-type="' + event.target.value + '"]')
            .find('input.ticket-search-edit-select')
            .prop('checked', true);
        
          uncheckNotOfType(event.target.value);
        } else {
          tickets
            .find('input.ticket-search-edit-select')
            .prop('checked', false);
        }
      
        updateEditButton();
      });
    
    $('#tickets-search-conditions li.submit')
      .append(
        $('<div></div>')
          .attr({'class': 'ticket-search-edit-multiple'})
          .append('Select ')
          .append(ticketEditSelect)
          .append(ticketEditButton)
      );
  }
  
  Tracker.Search = {
    init: function() {
      if ($('#tickets-search')[0]) {
        var search = $('#tickets-search').data('search');
        
        if (search && (search.fields && search.operators && search.values)) {
          $.each(search.fields, function(i, field) {
            addCondition(null, field, search.operators[i], search.values[i]);
          });
        } else {
          addCondition();
        }
        
        initTicketEdit();
      }
      
      quicksearch.q = $('#tickets-quicksearch-q')
        .keyup(function(event) {
          var q = quicksearch.q.val();
          
          if (quicksearch.timer) {
            clearTimeout(quicksearch.timer);
            quicksearch.timer = null;
          }
          
          if (q === '') {
            doSearch('');
          } else {
            quicksearch.timer = setTimeout(function() {
              quicksearch.timer = null;
              doSearch(q);
            }, 300);
          }
        });
      
      quicksearch.tickets = $('ul.tickets');
      
      $(document).on('keydown', function(event) {
        // Ctrl/Cmd + F
        if ((event.metaKey || event.ctrlKey) && event.which == 70) {
          quicksearch.q.focus();
          event.preventDefault();
        }
      });
    }
  };
}());


/*
  
  Edit ticket
  
*/
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

    for (var i = 0, id = assignee.select.data('current-user-id'); i < assignee.select[0].options.length; i++) {
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
  
  function enableParentInput(element) {
    element
      .closest('li')
      .find('input.ticket-edit-multiple-enable')
      .prop('checked', true)
      .change();
  }
  
  // TODO: implement stale ticket tracking and display
  Tracker.Edit = {
    init: function() {
      assignee.select = $('#ticket-edit-handle_id');
      assignee.description = $('<span> or </span>')
        .addClass('description')
        .insertAfter(assignee.select);
      
      $('<a></a>')
        .attr('href', '#')
        .text('Remove assignee')
        .click(removeAssignee)
        .prependTo(assignee.description);
      $('<a></a>')
        .attr('href', '#')
        .text('assign to me (' + assignee.select.data('current-user-name') + ')')
        .click(assignToUser)
        .appendTo(assignee.description);
      
      failed.checkbox = $('#ticket-edit-failed')
        .change(updateCommentField);;
      failed.initial = failed.checkbox[0].checked;
      
      needsAttention.checkbox = $('#ticket-edit-needs_attention')
        .change(updateCommentField);
      
      state.select = $('#ticket-edit-state').change(updateState);
      state.initial = state.select[0].selectedIndex;
      
      // TODO: add class "disable" if ticket is in intial state
      state.description = $('<span> or </span>')
        .addClass('description')
        .insertAfter(state.select);
      state.next = $('<a></a>')
        .attr('href', '#')
        .click(nextState)
        .prependTo(state.description);
      $('<a></a>')
        .attr('href', '#')
        .text('reset to initial')
        .click(resetState)
        .appendTo(state.description);
      
      state.encodingProfile = $('#ticket-edit-encoding_profile').parent();
      
      updateState();
    },
    
    Multiple: {
      init: function() {
        assignee.select = $('#ticket-edit-multiple-handle_id');
        assignee.description = $('<span> or </span>')
          .addClass('description')
          .insertAfter(assignee.select);
      
        $('<a></a>')
          .attr('href', '#')
          .text('Remove assignee')
          .click(function(event) {
            removeAssignee(event);
            enableParentInput($(event.target));
          })
          .prependTo(assignee.description);
        $('<a></a>')
          .attr('href', '#')
          .text('assign to me (' + assignee.select.data('current-user-name') + ')')
          .click(function(event) {
            assignToUser(event);
            enableParentInput($(event.target));
          })
          .appendTo(assignee.description);
        
        var fields = $('#ticket-edit-multiple fieldset:not(.ticket-edit-multiple-exclude) ul li')
          .prepend(
            $('<input></input>')
              .attr({
                'type': 'checkbox',
                'class': 'ticket-edit-multiple-enable'
              })
              .change(function(event) {
                $(this)
                  .parent()
                  .find('input:not(.ticket-edit-multiple-enable), select')
                  .prop('disabled', function(i, value) {
                    return !event.target.checked;
                  });
              })
          );
        
        fields
          .find('input.checkbox')
          .each(function() {
            var checkbox = $(this),
                hiddenField = $('<input></input>')
                  .attr({
                    'type': 'hidden',
                    'name': checkbox.prop('name'),
                    'value': '0'
                  })
                  .insertAfter(checkbox);
            
            checkbox.change(function(event) {
              hiddenField.val((checkbox.is(':checked'))? '1' : '0');
            });
          })
          .removeAttr('name');
        
        fields
          .find('input:not(.ticket-edit-multiple-enable), select')
          .prop('disabled', true);
        
        fields
          .find('label')
          .click(function(event) {
            var fieldEnable = $(this).siblings('.ticket-edit-multiple-enable');
            
            if (!fieldEnable.is(':checked')) {
              fieldEnable
                .prop('checked', true)
                .trigger('change');
              event.preventDefault();
            }
          });
      }
    }
  };
}());

/*
  
  Ticket index
  
*/
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
      
      tickets.wrap(
        $('<div></div>')
          .css({'overflow': 'hidden'})
      );
      
      url = $('#tickets-filter')[0].action;
      currentIndex = $('.ticket-header-bar li.current').data('ai');
      
      if (!currentIndex) {
        currentIndex = 1;
      }
      
      $('#tickets-filter .ticket-header-bar button').each(function(i, button) {
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

/*
  
  View ticket
  
*/
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
      
      var candidates = $(),
          logCount = 0;
      
      $('#timeline li').each(function(i, li) {
        li = $(li);
        
        if (li.hasClass('log')) {
          logCount++;
          
          if (logCount >= 2) {
            candidates = candidates.add(li);
          }
        } else if (li.hasClass('comment')) {
          if (candidates.length >= 3) {
            
            candidates = candidates
              .slice(0, -1)
              .hide();
            
            (function(candidates) {
              var unfold = $('<a></a>')
                .attr('href', '#')
                .text('Show ' + candidates.length + ' hidden entries')
                .appendTo(
                  $('<li></li>')
                    .addClass('event right unfold')
                    .insertBefore(candidates.first()
                  )
                )
                .click(function(event) {
                  event.preventDefault();
                  unfold.hide();
                  candidates.show();
                });
            })(candidates);
          }
          
          candidates = $();
          logCount = 0;
        }
      });
    }
  };
}());

/*
  
  Ticket actions
  
*/
(function() {
  var comment = null,
      delay = null,
      delayBy = null,
      expand = null,
      expandLeft = null,
      expandRight = null,
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

/*
  
  Feed
  
*/
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
                event.preventDefault();
                
                next.parent().hide();
                
                $.each(latestEntries, function(i, entry) {
                  $(entry)
                    .insertBefore(first)
                    .find('time[datetime]')
                    .each(function(i, time) {
                      new Tracker.Time(time);
                    });
                });
                
                latestEntries = [];
                next.parent().remove();
                next = null;
                updateTitle();
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
    }
  };
}());

/*
  
  Foldables
  
*/
(function() {
  
  function scrollToSection(section) {
    $('html, body').animate({
      scrollTop: section.offset().top
    }, 500);
  }
  
  Tracker.Foldable = function(fieldset) {
    fieldset = $(fieldset);
    
    var itemCount = fieldset.find('> ul > li').length;
    
    if (itemCount === 0) {
      return;
    }
    
    fieldset
      .addClass('folded')
      .find('legend')
      .append(' (' + itemCount + ')')
      .append(
        $('<a></a>')
          .attr({'href': '#', 'title': 'Expand section'})
          .text('expand')
          .click(function(event) {
            event.preventDefault();

            event.target = $(event.target);
            var section = event.target.parent().parent();

            if (section.hasClass('folded')) {
              event.target.attr('title', 'Fold section').text('fold');
              section.removeClass('folded').addClass('expanded');
              scrollToSection(section);
            } else {
              event.target.attr('title', 'Expand section').text('expand');
              section.removeClass('expanded').addClass('folded');
            }
          })
      );
  };
})();

/*
  
  Property editor
  
*/
(function() {
  function attachMultilineHandler(input) {
    input.on('keydown', function(event) {
      if (!event.altKey || event.keyCode !== 13) {
        return;
      }
      
      event.preventDefault();
      
      var textarea = $('<textarea></textarea>')
        .attr({
          'type': 'text',
          'name': input.attr('name'),
          'class': 'text wide',
          'data-property-index': input.attr('data-property-index')
        })
        .insertBefore(input)
        .val(input.val() + "\n")
        .focus();
      
      var length = textarea.val().length
      textarea[0].setSelectionRange(length, length);
      
      input.next('a.delete').remove();
      input.remove();
      
      appendDeleteButton.call(this, textarea);
    });
  }
  
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
        
        if (input.hasClass('delete')) {
          input[0].disabled = false;
          input.removeClass('delete');
          
          $(event.target)
            .removeClass('restore')
            .text('Delete ' + this.description)
            .attr('title', 'Delete ' + this.description);
          
            deleteField.detach();
        } else {
          input[0].disabled = true;
          input.addClass('delete');
          
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
            index,
            input,
            lastInput = this.list.find('[data-property-index]:last'),
            lastIndex = (lastInput[0])? lastInput.data('property-index') : -1,
            key;
            
        key = $('<input></input>')
          .attr({
            'type': 'text',
            'name': this.create.key.replace('[]', '[' + (lastIndex + 1) + ']'),
            'class': 'text'
          })
          .appendTo($('<label></label>').appendTo(container))
          .focus();
        
        index = this.keys.push(key[0]) - 1;
        
        input = $('<input></input>')
          .attr({
            'type': 'text',
            'name': this.create.value.replace('[]', '[' + (lastIndex + 1) + ']'),
            'class': 'text wide',
            'data-property-index': lastIndex + 1
          })
          .appendTo(container);
        
        attachMultilineHandler.call(this, input);
        
        this.create.hidden.each(function(i, hidden) {
          $(hidden)
            .clone()
            .attr('name', function(i, name) {
              return name.replace('[]', '[' + (lastIndex + 1) + ']');
            })
            .appendTo(container);
        });
        
        $('<a></a>')
          .attr({
            'href': '#',
            'title': 'Delete ' + this.description
          })
          .addClass('delete')
          .text('Delete ' + this.description)
          .click(function(event) {
            event.preventDefault();
            container.remove();
            this.keys.splice(this.keys.indexOf(key[0]), 1);
          }.bind(this))
          .appendTo(container);
      }.bind(this))
      .prependTo(
        $('<p></p>')
          .appendTo(
            $('<li><label></label></li>')
              .appendTo(this.list)
          )
          .append(
            $('<span></span>')
              .addClass('description')
              .text('Use Alt+Enter to input multiline values')
          )
      );
    
    this.insertBefore = this.createButton.closest('li');
  }
  
  Tracker.Properties = function(list) {
    this.list = $(list);
    this.description = this.list.data('properties-description') || 'property';
    this.create = {
      key: this.list.data('properties-create-key') || 'key',
      value: this.list.data('properties-create-value') || 'value',
      hidden: this.list.find('[data-properties-hidden="true"]').detach()
    };
    
    this.keys = [];
    
    this.list.find('li').each(function(i, li) {
      var input = $(li).children('[data-property-index]');
      appendDeleteButton.call(this, input);
      attachMultilineHandler.call(this, input);
    }.bind(this));
    
    this.list
      .closest('form')
      .submit(function(event) {
        $.each(this.keys, function() {
          if (this.value !== '') {
            return;
          }
          
          event.preventDefault();
          
          alert('Please specify a key for the property.');
          this.focus();
          
          return false;
        });
      }.bind(this));
    
    // TODO: read data-label
    
    appendCreateButton.call(this);
  };
})();

/*
  
  Import

*/
(function() {
  Tracker.Import = {
    init: function() {
      $('#ticket-import-list ul.tickets li.table').hide();
      $('#ticket-import-list ul.tickets')
        .each(function(i, ul) {
          var changesVisible = false,
              ul = $(ul);
          
          if (ul.find('li.table').length <= 0) {
            return;
          }
          
          $('<a></a>')
            .attr({
              'href': '#',
              'class': 'ticket-import-fold'
            })
            .text('expand changes')
            .click(function(event) {
              event.preventDefault();
              
              if (changesVisible) {
                ul.find('li.table').hide();
                $(event.target).text('expand changes');
              } else {
                ul.find('li.table').show();
                $(event.target).text('fold changes');
              }
              
              changesVisible = !changesVisible;
            })
            .appendTo(ul.prev('h3'));
        });
      
      // Invert selection button
      $('<a></a>')
        .attr('href', '#')
        .text('Invert selection')
        .click(function(event) {
          event.preventDefault();
          
          $('#ticket-import-list input[type=checkbox]').each(function(i, checkbox) {
            checkbox.checked = !checkbox.checked;
            $(checkbox).change();
          });
        })
        .appendTo($('#ticket-import-list fieldset:last ul li'));
    }
  };
})();

/*
  
  Editor
  
*/
(function() {
  function enterFullscreen() {
    this.editor.setOption("fullScreen", true);
    this.button
      .addClass('CodeMirror-fullscreen-button-exit')
      .attr('title', 'Exit fullscren (Esc)');
  }
  
  function exitFullscreen() {
    this.editor.setOption("fullScreen", false);
    this.button
      .removeClass('CodeMirror-fullscreen-button-exit')
      .attr('title', 'Enter fullscreen (Esc to exit)');
  }
  
  Tracker.Editor = function(textarea) {
    this.editor = CodeMirror.fromTextArea(textarea, {
      lineNumbers: true,
      // may affect performance but is needed to fit content
      viewportMargin: Infinity,
      readOnly: textarea.readOnly,
      autoCloseTags: true,
      extraKeys: {
        "Esc": function(editor) {
          if (!this.editor.getOption("fullScreen")) {
            return;
          }
          
          exitFullscreen.call(this);
        }.bind(this)
      }
    });
    
    this.button = $('<a></a>')
      .attr({
        'href': '#',
        'class': 'CodeMirror-fullscreen-button',
        'title': 'Enter fullscreen (Esc to exit)'
      })
      .text('fullscreen')
      .click(function(event) {
        if (!this.editor.getOption("fullScreen")) {
          enterFullscreen.call(this);
        } else {
          exitFullscreen.call(this);
        }
        
        event.preventDefault();
      }.bind(this))
      .appendTo(this.editor.display.wrapper);
    
    $(textarea).data('editor', this.editor);
  };
})();

/*
  
  Time
  
*/
(function() {
  var intervals = {
        'second': null,
        'minute': null
      },
      elements = {
        'second': [],
        'minute': []
      },
      weekdays = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
      ],
      shortMonths = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'
      ];
  
  function getFormattedTime(datetime) {
    return ('0' + datetime.getHours()).slice(-2) + ':' +
      ('0' + datetime.getMinutes()).slice(-2);
  }
  
  function timeRelativeDifference(datetime) {
    var now = new Date(),
        
        seconds = Math.round((now.getTime() - datetime.getTime()) / 1000),
        minutes = Math.round(seconds / 60),
        hours = Math.round(minutes / 60);
    
    if (seconds < 10) {
      return 'a second ago';
    } else if (seconds < 45) {
      return seconds + ' seconds ago';
    } else if (seconds < 90) {
      return 'a minute ago';
    } else if (minutes < 45) {
      return minutes + ' minutes ago';
    } else if (minutes < 90) {
      return 'an hour ago';
    } else if (hours < 7) {
      return hours + ' hours ago';
    }
    
    var days = ((hours + datetime.getHours()) / 24);
    
    if (days < 1) {
      return 'today at ' + getFormattedTime(datetime);
    } else if (days < 2) {
      return 'yesterday at ' + getFormattedTime(datetime);
    } else if (days < 7) {
      return weekdays[datetime.getDay()] + ' at ' + getFormattedTime(datetime);
    }
    
    var year = datetime.getFullYear();
    
    return 'on ' + shortMonths[datetime.getMonth()] + ' ' +
      ('0' + datetime.getDate()).slice(-2) +
      ((year !== now.getFullYear())? (', ' + year) : '');
  }
  
  function initHandlers() {
    if (elements.second.length > 0 && intervals.second === null) {
      intervals.second = setInterval(function() {
        if (elements.second <= 0) {
          clearInterval(intervals.second);
          return;
        }
        
        $.each(elements.second, function(i, time) {
          time.update();
          
          if (time.getDifference() >= 90) {
            elements.second.splice(i, 1);
            elements.minute.push(time);
          }
        });
      }, 1000);
    }
    
    if (elements.minute.length > 0 && intervals.minute === null) {
      intervals.minute = setInterval(function() {
        $.each(elements.minute, function(i, time) {
          time.update();
        });
      }, 60000);
    }
  }
  
  Tracker.Time = function(element) {
    this.element = $(element);
    this.datetime = new Date(this.element.attr('datetime'));
    
    if (this.getDifference() < 90) {
      elements.second.push(this);
    } else {
      elements.minute.push(this);
    }
    
    initHandlers();
  };
  
  Tracker.Time.prototype.getDifference = function() {
    return (((new Date()).getTime() - this.datetime.getTime()) / 1000);
  };
  
  Tracker.Time.prototype.update = function() {
    this.element.text(timeRelativeDifference(this.datetime));
  };
})();

$(function() {
  $('#ticket-action-comment.hidden').parent().hide();
  // $('#ticket-action-expand_by').parent().hide();
  
  if ($('#ticket-edit-state optgroup')[0]) {
    $('#ticket-edit-encoding_profile').parent().hide();
  }
  
  if ($('#tickets-quicksearch')[0]) {
    Tracker.Search.init();
  }
  
  if ($('#ticket-edit')[0]) {
    Tracker.Edit.init();
  } else if ($('#ticket-edit-multiple')[0]) {
    Tracker.Edit.Multiple.init();
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
  
  $('fieldset.foldable').each(function() {
    new Tracker.Foldable(this);
  });
  
  $('ul.edit-properties').each(function() {
    new Tracker.Properties(this);
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
  
  $('textarea[data-has-editor]').each(function(i, textarea) {
    new Tracker.Editor(textarea);
  });
  
  if ($('#ticket-import-list')[0]) {
    Tracker.Import.init();
  }
  
  $('time[datetime]').each(function(i, time) {
    new Tracker.Time(time);
  });
  
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
              'title': 'Show XML template'
            })
            .text('show template')
            .click(function(event) {
              event.preventDefault();
              
              var target = $(event.target);
              
              if (tr.is(':hidden')) {
                tr.show();
                target
                  .text('hide template')
                  .attr('title', 'Hide XML template');
                tr.find('textarea').data('editor').refresh();
              } else {
                tr.hide();
                target
                  .text('show template')
                  .attr('title', 'Show XML template');
              }
            })
        );
    });
  
  $('select[data-submit-on-change], input[type="checkbox"][data-submit-on-change]')
    .change(function(event) {
      event.target.form.submit();
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
  
  $('input[data-association-destroy]')
    .change(function(event) {
      var target = $(event.target);
    
      if (!event.target.checked) {
        target.data(
          'destroy',
          $('<input>')
            .attr({
              'type': 'hidden',
              'name': target.data('association-destroy'),
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
  
  $('[data-dialog-confirm]').click(function(event) {
    if (!confirm($(this).data('dialog-confirm'))) {
      event.preventDefault();
    }
  });
});