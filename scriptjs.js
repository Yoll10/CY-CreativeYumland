
function toggleDarkMode() {
    const body = document.body;
    const btn = document.getElementById('dark-mode-btn');
    
    body.classList.toggle('dark-mode');
    
    if (body.classList.contains('dark-mode')) {
        if(btn) btn.innerHTML = "☀️";
        localStorage.setItem('theme', 'dark');
    } else {
        if(btn) btn.innerHTML = "🌙";
        localStorage.setItem('theme', 'light');
    }
}
