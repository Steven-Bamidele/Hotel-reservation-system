/**
 * CSC 476 Project
 * Hotel Reservation and Management System (UI Hotels)
 * Department of Computer Science, University of Ibadan
 * Lead Developers: Bamidele Steven Oluwadamilare & Lawal Kamaldeen
 */
document.addEventListener('DOMContentLoaded', function () {
  // Elements from the search bar & modal
  const checkinInput = document.getElementById('searchCheckin');
  const checkoutInput = document.getElementById('searchCheckout');
  const modal = document.getElementById('reservationModal');
  const closeModalBtn = document.querySelector('.btn-close');

  const modalRoomName = document.getElementById('modalRoomName');
  const modalRoomRate = document.getElementById('modalRoomRate');
  const modalCheckin = document.getElementById('modalCheckin');
  const modalCheckout = document.getElementById('modalCheckout');
  const durationText = document.getElementById('stayDuration');
  const totalAmountText = document.getElementById('stayTotal');

  // 1. Prevent selecting past dates
  const today = new Date().toISOString().split('T')[0];
  if (checkinInput) {
    checkinInput.min = today;
  }
  if (modalCheckin) {
    modalCheckin.min = today;
  }

  // Set checkout minimum date according to check-in
  if (checkinInput && checkoutInput) {
    checkinInput.addEventListener('change', function () {
      checkoutInput.min = this.value;
    });
  }

  // 2. Open Modal when clicking "Reserve Room"
  const bookButtons = document.querySelectorAll('.btn-book');
  bookButtons.forEach(button => {
    button.addEventListener('click', function () {
      const roomTitle = this.getAttribute('data-name');
      const roomPrice = parseFloat(this.getAttribute('data-price'));

      modalRoomName.value = roomTitle;
      modalRoomRate.value = roomPrice;

      // Pass checkin/checkout dates if user already picked in search widget
      if (checkinInput && checkinInput.value) {
        modalCheckin.value = checkinInput.value;
      } else {
        modalCheckin.value = today;
      }

      // Default checkout to the following day
      const nextDay = new Date();
      nextDay.setDate(nextDay.getDate() + 1);
      const nextDayFormatted = nextDay.toISOString().split('T')[0];

      if (checkoutInput && checkoutInput.value) {
        modalCheckout.value = checkoutInput.value;
      } else {
        modalCheckout.value = nextDayFormatted;
      }

      modalCheckout.min = modalCheckin.value;

      calculateStayTotal();
      modal.style.display = 'flex';
    });
  });

  // 3. Dynamic Price and Night Calculation
  function calculateStayTotal() {
    const nightlyPrice = parseFloat(modalRoomRate.value) || 0;
    const start = new Date(modalCheckin.value);
    const end = new Date(modalCheckout.value);

    if (end > start) {
      const timeDifference = Math.abs(end - start);
      const daysCount = Math.ceil(timeDifference / (1000 * 60 * 60 * 24));
      const totalAmount = daysCount * nightlyPrice;

      durationText.textContent = daysCount + ' Night(s)';
      totalAmountText.textContent = '₦' + totalAmount.toLocaleString();
    } else {
      durationText.textContent = '0 Nights';
      totalAmountText.textContent = '₦0 (Invalid Dates)';
    }
  }

  if (modalCheckin && modalCheckout) {
    modalCheckin.addEventListener('change', function () {
      modalCheckout.min = this.value;
      calculateStayTotal();
    });
    modalCheckout.addEventListener('change', calculateStayTotal);
  }

  // 4. Modal Close Handling
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', function () {
      modal.style.display = 'none';
    });
  }

  window.addEventListener('click', function (event) {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  });

  // 5. Client Submission Notice
  const bookingForm = document.getElementById('clientBookingForm');
  if (bookingForm) {
    bookingForm.addEventListener('submit', function (e) {
      ;
      alert('Front-end validated! Next phase: Connecting to MySQL database via PHP.');
      modal.style.display = 'none';
    });
  }
});