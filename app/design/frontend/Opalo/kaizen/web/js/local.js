// Filter Popover
function filterToggle (filterTitle, btnEl, htmlEl, parent) {
  btnEl.popover({
    container:(parent !== undefined) ? parent : 'body',
    html: true, 
    placement: "auto",
    content: function() {
      return htmlEl.html();
    },
    title: filterTitle+'<button type="button" id="close" class="icon-cross close"></button>',
    template: '<div class="popover filter-orders-form" role="tooltip"><div class="arrow"></div><div class="popover-title"></div><div class="popover-content"></div></div>'
  });
};
      
jQuery(document).ready(function($) {
  
  // Remove duplicate form ID issue in filter popover
  var $filterContentHtml = $('#filter-form');
  
  if($filterContentHtml.length) {

    var form = $filterContentHtml.find('.filter-form');
    
    $('#filter-orders-toggle')
      .on('shown.bs.popover', function() {
      form.remove();
      form = $('.popover-content').find('.filter-form');
    })
      .on('hidden.bs.popover', function() {
      form.prependTo($filterContentHtml);
    });

  }

  // Close Popover using .close btn
  $(document).on("click", ".popover .close" , function(){
      $(this).parents(".popover").popover('hide');
  });
  
  // Nav hehavior for mobile 
  $('#sidebar-mobile').click(function() {
    $('#site-nav').toggleClass('hide-sm');
  });
        
  // Sticky for Page Title
  var sticky = $('.site-main .page-title'),
      stickyPh = $('<div class="page-title-ph"></div>'),
      stickyHeight = sticky.height();
  
  sticky.after(stickyPh);
  stickyPh.css({'height':stickyHeight,'display':'none'});
      
  $(window).scroll(function(){
  
    var scroll = $(window).scrollTop();
    
    if (scroll >= 70) {
      sticky.addClass('fixed');
      stickyPh.css({'display':'block'});
    } else {
      sticky.removeClass('fixed');
      stickyPh.css({'display':'none'});
    }
  });
  
  // Make tables responsive-ready
  $('table.data-table, table.table, table.data').wrap('<div class="table-responsive"></div>');
  
  // Add calendar icon next to form input
  $('img[src$="calendar.gif"],img[src$="grid-cal.gif"]').prev('.form-control,.input-text').addClass('date-selector');
  
});