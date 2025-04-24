<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arabic Letters Animation</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Amiri', 'Scheherazade New', serif;
            overflow: hidden;
            background: linear-gradient(135deg, #5de1e6 0%, #00796B 100%);
        }
        
        .container {
            position: relative;
            height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1;
        }
        
        .search-container {
            width: 80%;
            max-width: 800px;
            text-align: center;
            color: white;
            padding: 20px;
            border-radius: 10px;
            background-color: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        p {
            margin-bottom: 25px;
            font-size: 1.2rem;
        }
        
        .search-box {
            display: flex;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 30px 0 0 30px;
            font-size: 1rem;
            outline: none;
            font-family: inherit;
        }
        
        .search-btn {
            background-color: #004D40;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 0 30px 30px 0;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: inherit;
        }
        
        .search-btn:hover {
            background-color: #00695C;
        }
        
        /* Category styles - Modified for two rows at full width */
        .categories-section {
            width: 100%;
            padding: 0 20px;
            box-sizing: border-box;
        }
        
        .category-row {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .category-btn {
            background-color: rgba(121, 36, 89, 0.8);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            flex: 1;
            margin: 0 5px;
            font-family: inherit;
        }
        
        .category-btn.small {
            background-color: #004D40;
        }
        
        .category-btn:hover {
            background-color: #00695C;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        .category-icon {
            margin-right: 5px;
            font-weight: bold;
            color: #ff8a65;
        }
        
        .letters-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .letter {
            position: absolute;
            color: rgba(255, 255, 255, 0.3);
            font-size: 30px;
            animation: fallDown linear infinite;
        }
        
        @keyframes fallDown {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(calc(100vh + 50px)) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="letters-background" id="letters-container"></div>
    
    <div class="container">
        <!-- Search container -->
        <div class="search-container">
            <h1>ابحث عن كتابك المفضل</h1>
            <p>ابحث في مجموعتنا الكبيرة من الكتب عبر الأنواع والتصنيفات.</p>
            
            <div class="search-box">
                <input type="text" class="search-input" placeholder="ابحث عن كتاب بالعنوان، المؤلف، أو النوع">
                <button class="search-btn">بحث</button>
            </div>
        </div>
        
        <!-- Categories section - Modified to have only two rows -->
        <div class="categories-section">
            <!-- First row with 6 categories -->
            <div class="category-row">
                <button class="category-btn small">روايات</button>
                <button class="category-btn small">تربية الأطفال والناشئين</button>
                <button class="category-btn small">كوميكس</button>
                <button class="category-btn small">صحة نفسية</button>
                <button class="category-btn small">التعامل مع المراهقين</button>
                <button class="category-btn small">أدب وتراث</button>
            </div>
            
            <!-- Second row with remaining categories -->
            <div class="category-row">
                <button class="category-btn small">المزيد</button>
                <button class="category-btn small">مطبخ وأطعمة شرقية</button>
                <button class="category-btn small">اقتصاد</button>
                <button class="category-btn small">دينية وتصوف <span class="category-icon">♥</span></button>
                <button class="category-btn small">تنمية ذاتية</button>
                <button class="category-btn small">أطعمة رمضان <span class="category-icon">♥</span></button>
            </div>
        </div>
    </div>

    <script>
        const lettersContainer = document.getElementById('letters-container');
        const arabicLetters = ['ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي'];
        
        // Create letters dynamically
        function createLetters() {
            // Initial batch of letters
            for (let i = 0; i < 120; i++) {
                createLetter();
            }
            
            // Continue adding letters over time
            setInterval(createLetter, 1000);
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
                letter.remove();
            }, (duration + delay) * 1000);
        }
        
        // Start the animation when the page loads
        window.addEventListener('load', createLetters);
    </script>
</body>
</html>