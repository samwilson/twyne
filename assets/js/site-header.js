(function () {
    const mainMenu = document.querySelector('.main-menu');
    const underlay = document.querySelector('.opaque-underlay');
    document.querySelector('.main-menu img').onclick = () => {
        if (mainMenu.classList.contains('menu-closed')) {
            mainMenu.classList.add('menu-open');
            mainMenu.classList.remove('menu-closed');
            underlay.style.display = 'block';
        } else {
            mainMenu.classList.remove('menu-open');
            mainMenu.classList.add('menu-closed');
            underlay.style.display = 'none';
        }
    };
})();
