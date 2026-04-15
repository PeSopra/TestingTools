// Generated from Figma Design - Interactive Features
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded successfully');
    
    // Add smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Newsletter form handling (if present)
    const signUpButtons = document.querySelectorAll('.text.sign-up');
    signUpButtons.forEach(button => {
        button.addEventListener('click', function() {
            const emailInput = this.parentElement.querySelector('.text.your-email');
            if (emailInput) {
                alert('Newsletter signup functionality would be implemented here');
            }
        });
    });
});
