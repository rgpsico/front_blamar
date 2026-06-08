$(document).ready(function(){
  $(".galeria_box02 img").click(function(){
    var newSrc = $(this).attr("src");
    var $main = $(".galeria_box01");
    var $mainImg = $main.find("img");

    if ($mainImg.length) {
      $mainImg.stop(true, true).fadeOut(150, function(){
        $mainImg.attr("src", newSrc).fadeIn(200);
      });
    } else {
      $main.find("iframe").remove();
      $("<img>", {
        src: newSrc,
        alt: "Imagem principal do hotel",
        css: { display: "none" }
      }).appendTo($main).fadeIn(200);
    }
  });

  // Mantem o clique nas thumbs apenas trocando a imagem principal
  $(".galeria_box02 p").click(function(){
    $("#hotel_galeria").fadeIn("slow");
  });

  $(".title_gallery i").click(function(){
    $("#hotel_galeria").fadeOut("slow");
  });
});
