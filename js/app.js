document.addEventListener('DOMContentLoaded', () => {
  // Fade out success/info alerts; keep errors visible until the user acts.
  setTimeout(() => {
    document.querySelectorAll('.alert-success').forEach((a) => {
      a.style.transition = 'opacity .3s ease';
      a.style.opacity = '0';
      setTimeout(() => a.remove(), 300);
    });
  }, 5000);

  // Confirmation prompts for destructive actions.
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (ev) => {
      if (!window.confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });

  // Booking cost estimate: hourly price × duration.
  const priceEl = document.getElementById('price_per_hour');
  const startEl = document.getElementById('start_time');
  const endEl = document.getElementById('end_time');
  const previewEl = document.getElementById('amount_preview');
  const hoursEl = document.getElementById('sum_hours');
  if (priceEl && startEl && endEl && previewEl) {
    const toMinutes = (v) => { const [h, m] = v.split(':').map(Number); return h * 60 + m; };
    const calc = () => {
      const price = Number(priceEl.value || 0);
      if (!price || !startEl.value || !endEl.value) { previewEl.textContent = '—'; if (hoursEl) hoursEl.textContent = '—'; return; }
      const minutes = toMinutes(endEl.value) - toMinutes(startEl.value);
      if (minutes <= 0) {
        previewEl.textContent = 'End time must be after start';
        if (hoursEl) hoursEl.textContent = '—';
        return;
      }
      const hours = minutes / 60;
      if (hoursEl) hoursEl.textContent = `${Number(hours.toFixed(2))} hr`;
      previewEl.textContent = '₹' + (hours * price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    [startEl, endEl].forEach((el) => el.addEventListener('input', calc));
    calc();
  }

  // Theme / amenity suggestion chips toggle values in a comma-separated input.
  document.querySelectorAll('.chip-list[data-target]').forEach((list) => {
    const input = document.getElementById(list.dataset.target);
    if (!input) return;
    const values = () => input.value.split(',').map((v) => v.trim()).filter(Boolean);
    const sync = () => {
      const current = values().map((v) => v.toLowerCase());
      list.querySelectorAll('.chip').forEach((chip) => chip.classList.toggle('selected', current.includes(chip.textContent.trim().toLowerCase())));
    };
    list.querySelectorAll('.chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        const label = chip.textContent.trim();
        const current = values();
        const idx = current.findIndex((v) => v.toLowerCase() === label.toLowerCase());
        if (idx >= 0) current.splice(idx, 1); else current.push(label);
        input.value = current.join(', ');
        sync();
      });
    });
    input.addEventListener('input', sync);
    sync();
  });

  // Local preview of a newly chosen venue photo, with client-side size/type hints.
  const photoInput = document.getElementById('photo');
  const preview = document.getElementById('photo-preview');
  if (photoInput && preview) {
    photoInput.addEventListener('change', () => {
      const file = photoInput.files && photoInput.files[0];
      if (!file) return;
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Please choose a JPG, PNG or WebP image.');
        photoInput.value = '';
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        alert('Photo must be 5 MB or smaller.');
        photoInput.value = '';
        return;
      }
      const img = document.createElement('img');
      img.className = 'space-photo space-photo-sm';
      img.alt = 'Selected photo preview';
      img.src = URL.createObjectURL(file);
      preview.replaceChildren(img);
    });
  }
});
