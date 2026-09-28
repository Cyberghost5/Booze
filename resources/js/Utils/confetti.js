import confetti from 'canvas-confetti';

/**
 * Triggers a vibrant, multi-burst success confetti animation
 * for completed orders across Rider, Vendor, and Consumer views.
 */
export function fireSuccessConfetti() {
    const duration = 2.5 * 1000;
    const animationEnd = Date.now() + duration;
    const defaults = {
        startVelocity: 30,
        spread: 360,
        ticks: 60,
        zIndex: 99999,
        colors: ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#ffffff'],
    };

    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    // Initial festive burst from bottom center
    confetti({
        particleCount: 100,
        spread: 100,
        origin: { y: 0.6 },
        zIndex: 99999,
        colors: ['#f59e0b', '#10b981', '#fbbf24', '#34d399', '#ffffff'],
    });

    // Continuous fireworks rain for 2.5 seconds
    const interval = setInterval(function () {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        const particleCount = 50 * (timeLeft / duration);

        // Burst from left side
        confetti({
            ...defaults,
            particleCount,
            origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
        });

        // Burst from right side
        confetti({
            ...defaults,
            particleCount,
            origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
        });
    }, 250);
}
