var Tracker = {Dashboard: {Update: {}, Mentions: {}}};

(function() {
  var elements = {
        'action-count-cutting': null,
        'action-count-checking': null,
        'action-count-fixing': null,
        'contributor-cutting': null,
        'contributor-checking': null,
        'contributor-fixing': null
      },
      baseURL = '';
  
  // TODO: do not update anything, if page is not visible
  function updateElements(action, onSuccess) {
    $.ajax({
      url: baseURL + action + '.json',
      type: 'GET',
      dataType: 'json',
      success: function(result, status) {
        if (!result) {
          return;
        }
        
        $.each(result, function(id, value) {
          if (elements[id]) {
            if (value) {
              elements[id].text(value);
            } else {
              elements[id].text('–');
            }
          }
        });
        
        if (onSuccess) {
          onSuccess.call(null);
        }
      }
    });
  }
  
  function updateActions() {
    updateElements('actions', function() {
      setTimeout(updateActions, 5000);
    })
  }
  
  function updateViewers() {
    $.ajax({
      url: 'http://stats.28c3.fem-net.de/streams.php?jsonp=?',
      dataType: 'jsonp',
      success: function(result, status) {
        for (var room = 1; room <= 3; room++) {
          $('#viewer-total-room-' + room).text(result['saal' + room]);
        }
        
        setTimeout(updateViewers, 60000);
      }
    })
  }
  
  Tracker.Dashboard.Update.init = function() {
    // TODO: is there better way to do this?
    baseURL = document.URL + '/';
    
    $.each(elements, function(element) {
      elements[element] = $('#' + element);
    });
    
    updateActions();
    updateViewers();
  }
}());

(function() {
  var current = null,
      refreshURL = '?q=c3streaming',
      params = '&result_type=recent&rpp=100',
      stripe = null,
      keywords = /((@|#)\w*)/gi;
  
  function updateMentions(onSuccess) {
    $.ajax({
      url: 'http://search.twitter.com/search.json' + refreshURL + params,
      type: 'GET',
      dataType: 'jsonp',
      success: function(result, status) {
        if (status != 'success' || !result) {
          return;
        }
        
        if (result.refresh_url) {
          refreshURL = result.refresh_url;
        }
        
        $.each(result.results, function(i, mention) {
          $('<li></li>')
            .append($('<p></p>').append($('<span></span>').html(mention.text.replace(keywords,'<span class="keyword">$1</span>'))))
            .prepend($('<img></img>').attr({
              'src': mention.profile_image_url,
              'alt': mention.from_user_name,
              'title': mention.from_user_name
            })).appendTo(stripe);
        });
        
        if (onSuccess) {
          onSuccess.call(null);
        }
      }
    });
  }
  
  function scheduleNext() {
    if (!current[0]) {
      return;
    }
    
    var text = current.find('p'),
        difference = text[0].scrollWidth - text[0].offsetWidth,
        animationTime = 0;
    
    if (difference > 0) {
      animationTime = Math.pow(Math.log(difference)/Math.log(10),3)*500;
      
      text.delay(1000).animate({'scrollLeft': difference}, animationTime);
      current.delay(animationTime + 3000);
    } else {
      current.delay(6000);
    }
    
    current.queue(next);
  }
  
  function next(dequeue) {
    current.animate({'margin-top': '-58px'}, 1000).queue(function(dequeue) {
      current = current.css({'margin-top': '0px'}).hide().next();
      
      if (!current) {
        // TODO
      }
      
      scheduleNext();
      
      dequeue();
    });
    
    dequeue();
  }
  
  Tracker.Dashboard.Mentions.init = function() {
    stripe = $('#mentions-stripe');
    updateMentions(function() {
      current = stripe.find('li:first-child');
      scheduleNext();
    });
  }
}());

$(function() {
  Tracker.Dashboard.Update.init();
  Tracker.Dashboard.Mentions.init();
});