$(window).ready(function(){
  $('.menu-toggle').click(function(){
    //console.log(2);
    $('.main-nav').toggleClass('main-nav-open',500);
    $(this).toggleClass('open');
  });
});