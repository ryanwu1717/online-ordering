import React, { useEffect, useState, useRef, forwardRef } from "react";
import {
    MagnifierContainer,
    MagnifierPreview,
    MagnifierZoom
} from "react-image-magnifiers";
import './MagnifierTest.css';


function MagnifierTest() {
    const [canvasLeft, setCanvasLeft] = useState(0);
    const [canvasTop, setCanvasTop] = useState(0);
    const [canvasHeight, setCanvasHeight] = useState(100);
    const [canvasWidth, setCanvasWidth] = useState(100);
    const canvasRef = useRef(null);
    const targetImage = useRef(null);

    useEffect(() => {
        let canvas = canvasRef.current;
        setCanvasWidth(canvas.width);
        setCanvasHeight(canvas.height);
    }, []);

    const MagnifierPreview = forwardRef((props, ref) => {
        return <MagnifierPreview ref={ref} {...props} />;
    });

    const MagnifierZoom = forwardRef((props, ref) => {
        return <MagnifierZoom ref={ref} {...props} />;
    });

    const handleMouseMoveEvent = (e) => {
        let offsetX = e.nativeEvent.offsetX;
        let offsetY = e.nativeEvent.offsetY;
        let startX = offsetX - 50;
        startX = startX < 0 ? 0 : startX;
        let startY = offsetY - 50;
        startY = startY < 0 ? 0 : startY;
        console.log(canvasRef)
        let canvasContext_temp = canvasRef.current.getContext('2d');
        canvasContext_temp.clearRect(0, 0, canvasWidth, canvasHeight);
        canvasContext_temp.drawImage(targetImage.current, startX, startY, 100, 100, 0, 0, 400, 400);
        setCanvasLeft(offsetX + 10)
        setCanvasTop(offsetY + 10)
    }

    return (
        <MagnifierContainer>
             <div className="position-relative" onMouseMove={handleMouseMoveEvent}>
                 {/* <MagnifierPreview style={{ position: 'absolute' }} ref={targetImage} imageSrc={'https://gimg2.baidu.com/image_search/src=http%3A%2F%2Fimg.pconline.com.cn%2Fimages%2Fupload%2Fupc%2Ftx%2Fphotoblog%2F1207%2F24%2Fc2%2F12516314_12516314_1343096689531.jpg&refer=http%3A%2F%2Fimg.pconline.com.cn&app=2002&size=f9999,10000&q=a80&n=0&g=0n&fmt=jpeg?sec=1639571698&t=343685a1f9467d6fa8cc27b44f955281'} /> */}
                 <MagnifierZoom className="position-absolute" ref={canvasRef} style={{ height: "100px", width: '100px', left: canvasLeft, top: canvasTop }} imageSrc={'https://gimg2.baidu.com/image_search/src=http%3A%2F%2Fimg.pconline.com.cn%2Fimages%2Fupload%2Fupc%2Ftx%2Fphotoblog%2F1207%2F24%2Fc2%2F12516314_12516314_1343096689531.jpg&refer=http%3A%2F%2Fimg.pconline.com.cn&app=2002&size=f9999,10000&q=a80&n=0&g=0n&fmt=jpeg?sec=1639571698&t=343685a1f9467d6fa8cc27b44f955281'} />
             </div>
        </MagnifierContainer>
    );
}


export default MagnifierTest;