// Synthesized bell for incoming order notifications (no audio file needed).
let ctx;

const getContext = () => {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return null;
    ctx ??= new AudioCtx();
    return ctx;
};

// Browsers only allow audio after a user gesture, so unlock on the first interaction.
const unlock = () => {
    const audio = getContext();
    if (audio?.state === 'suspended') audio.resume().catch(() => {});
};
['pointerdown', 'keydown', 'touchstart'].forEach((type) =>
    window.addEventListener(type, unlock, { once: true, passive: true }),
);

export function playBell() {
    const audio = getContext();
    if (!audio || audio.state !== 'running') return; // not unlocked yet

    const now = audio.currentTime;
    const master = audio.createGain();
    master.gain.setValueAtTime(0.0001, now);
    master.gain.exponentialRampToValueAtTime(0.5, now + 0.01);
    master.gain.exponentialRampToValueAtTime(0.0001, now + 1.6);
    master.connect(audio.destination);

    // A fundamental plus higher partials gives the bell timbre.
    [[880, 1], [1760, 0.5], [2640, 0.25], [3520, 0.12]].forEach(([freq, level]) => {
        const osc = audio.createOscillator();
        const gain = audio.createGain();
        osc.type = 'sine';
        osc.frequency.value = freq;
        gain.gain.value = level;
        osc.connect(gain).connect(master);
        osc.start(now);
        osc.stop(now + 1.7);
    });
}
