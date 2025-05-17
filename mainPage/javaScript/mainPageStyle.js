// app.js
document.addEventListener('DOMContentLoaded', function() {
    // Add tooltips to icons
    const icons = document.querySelectorAll('.icon-sidebar .icon');
    const iconNames = ['Home', 'Friends', 'Messages', 'Notifications', 'Settings'];
    
    icons.forEach((icon, index) => {
        // Create tooltip element
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = iconNames[index];
        
        // Position tooltip
        tooltip.style.position = 'absolute';
        tooltip.style.left = '80px';
        tooltip.style.backgroundColor = '#333';
        tooltip.style.color = '#fff';
        tooltip.style.padding = '5px 10px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.fontSize = '12px';
        tooltip.style.opacity = '0';
        tooltip.style.transition = 'opacity 0.3s';
        tooltip.style.pointerEvents = 'none';
        tooltip.style.zIndex = '100';
        
        // Add tooltip to icon
        icon.style.position = 'relative';
        icon.appendChild(tooltip);
        
        // Show tooltip on hover
        icon.addEventListener('mouseenter', () => {
            tooltip.style.opacity = '1';
        });
        
        // Hide tooltip when not hovering
        icon.addEventListener('mouseleave', () => {
            tooltip.style.opacity = '0';
        });
        // reload the page depending on the bouton clicked
        const settings= document.getElementsByClassName("home12")
        settings[0].addEventListener('click',()=>{
            const settingsPage= document.querySelector('.h-100.gradient-custom-2')
            const main = document.querySelector('.main-content')
            main.innerHTML= settingsPage
        })

    });
});