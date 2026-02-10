window.dragElement = (element, handle, dotNetHelper, callbackName) => {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    
    // Add mousedown listener to handle for dragging
    handle.onmousedown = dragMouseDown;
    
    // Ensure clicking the element itself (even if not dragging) notifies Blazor it was clicked/focused
    // But we need to be careful not to trigger double events if handle is clicked.
    // The C# side uses @onmousedown on the container, which is usually sufficient for Z-index.
    // However, if iframes capture clicks, we might need an overlay.

    function dragMouseDown(e) {
        e = e || window.event;
        e.preventDefault();
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        element.style.top = (element.offsetTop - pos2) + "px";
        element.style.left = (element.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
        if (dotNetHelper && callbackName) {
            dotNetHelper.invokeMethodAsync(callbackName, element.offsetLeft, element.offsetTop);
        }
    }
};

// Global Keyboard Handler
window.registerGlobalKeyboardHandler = (dotNetHelper) => {
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dotNetHelper.invokeMethodAsync('OnKeyPress', 'Escape');
        } else if (e.key === 'ArrowLeft') {
            dotNetHelper.invokeMethodAsync('OnKeyPress', 'ArrowLeft');
        } else if (e.key === 'ArrowRight') {
            dotNetHelper.invokeMethodAsync('OnKeyPress', 'ArrowRight');
        }
    });
};
