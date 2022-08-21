
    $.fn.EnlargeElement = function(_width,_height){
      var obj = this;
      var width = _width;
      var height = _height;
      $( obj ).mouseover(function() {
        if($( window ).width()>800){
            if(width!=null)
                $( obj ).css({"min-width": width});
            if(height!=null)
                $( obj ).css({"min-height": height});
            $(obj).css('z-index', 1); 
        }
      });
      $( obj ).mouseout(function() {
        $( obj ).css({"min-width": ""});
        $( obj ).css({"min-height": ""});
      });
      return obj;
    }