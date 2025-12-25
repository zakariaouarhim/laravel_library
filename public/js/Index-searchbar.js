document.addEventListener('DOMContentLoaded', function() {
    const lettersContainer = document.getElementById('letters-container');
    if (!lettersContainer) return; // Exit if element not found
    
    const arabicLetters = ['ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي'];
    
    // Create letters dynamically
    function createLetters() {
        // Initial batch of letters
        for (let i = 0; i < 60; i++) {
            createLetter();
        }
        
        // Continue adding letters over time
        setInterval(createLetter, 500);
    }
    
    function createLetter() {
        const letter = document.createElement('div');
        letter.className = 'letter';
        
        const randomLetter = arabicLetters[Math.floor(Math.random() * arabicLetters.length)];
        letter.textContent = randomLetter;
        
        // Random positioning and animation properties
        const size = Math.random() * 40 + 20; // Size between 20px and 60px
        const leftPos = Math.random() * 100; // Position from left 0% to 100%
        const duration = Math.random() * 15 + 10; // Animation duration between 10s and 25s
        const delay = Math.random() * 10; // Delay between 0s and 10s
        const opacity = Math.random() * 0.4 + 0.1; // Opacity between 0.1 and 0.5
        
        letter.style.fontSize = `${size}px`;
        letter.style.left = `${leftPos}%`;
        letter.style.animationDuration = `${duration}s`;
        letter.style.animationDelay = `${delay}s`;
        letter.style.opacity = opacity;
        
        lettersContainer.appendChild(letter);
        
        // Remove the letter after animation completes to prevent memory issues
        setTimeout(() => {
            if (letter.parentNode === lettersContainer) {
                lettersContainer.removeChild(letter);
            }
        }, (duration + delay) * 1000);
    }
    
    // Start the animation when the page loads
    createLetters();
});

// header.js



    
