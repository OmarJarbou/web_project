<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles.css">
  <title>Add Product</title>
  <style>
    /* Overlay and modal styling */
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      width: 400px;
      max-width: 90%;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      animation: fadeIn 0.3s ease-in-out;
    }

    .modal h2 {
      margin-top: 0;
      color: #333;
      font-size: 1.5rem;
    }

    .modal form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .modal input[type="text"],
    .modal input[type="number"],
    .modal input[type="file"],
    .modal button {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 1rem;
    }

    .modal button {
      background: #007bff;
      color: #fff;
      cursor: pointer;
      border: none;
      transition: background 0.3s;
    }

    .modal button:hover {
      background: #0056b3;
    }

    .category-select {
      position: relative;
    }

    .category-select button {
      display: block;
      text-align: left;
      background: #f8f9fa;
    }

    .category-options {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      width: 100%;
      background: #fff;
      border: 1px solid #ccc;
      max-height: 120px;
      overflow-y: auto;
      z-index: 10;
      border-radius: 5px;
    }

    .category-options label {
      display: block;
      padding: 10px;
      cursor: pointer;
    }

    .category-options input[type="radio"] {
      margin-right: 10px;
    }

    .close-modal {
      background: none;
      border: none;
      color: #888;
      font-size: 1.2rem;
      cursor: pointer;
      position: absolute;
      top: 10px;
      right: 10px;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Add Product Button -->
    <div id="new-card" class="new-card">Add New Product</div>

    <!-- Modal Structure -->
    <div class="overlay" id="productModal">
      <div class="modal">
        <button class="close-modal" onclick="closeModal()">&times;</button>
        <h2>Add New Product</h2>
        <form id="productForm">
          <!-- Name Field -->
          <input type="text" name="name" placeholder="Product Name" required>

          <!-- Category Selection -->
          <div class="category-select">
            <button type="button" onclick="toggleCategoryOptions()">Select Category</button>
            <div class="category-options" id="categoryOptions">
              <label><input type="radio" name="category" value="Electronics"> Electronics</label>
              <label><input type="radio" name="category" value="Clothing"> Clothing</label>
              <label><input type="radio" name="category" value="Home"> Home</label>
              <label><input type="radio" name="category" value="Books"> Books</label>
              <label><input type="radio" name="category" value="Toys"> Toys</label>
              <!-- Add more categories here -->
            </div>
          </div>

          <!-- Cost Field -->
          <input type="number" name="cost" placeholder="Product Cost" required>

          <!-- Quantity Field -->
          <input type="number" name="quantity" placeholder="Product Quantity" required>

          <!-- Image Field -->
          <input type="file" name="image" accept="image/*" required>

          <!-- Submit Button -->
          <button type="submit">Add Product</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    const modal = document.getElementById('productModal');
    const categoryOptions = document.getElementById('categoryOptions');

    document.getElementById('new-card').addEventListener('click', () => {
      modal.style.display = 'flex';
    });

    function closeModal() {
      modal.style.display = 'none';
    }

    function toggleCategoryOptions() {
      categoryOptions.style.display = categoryOptions.style.display === 'block' ? 'none' : 'block';
    }

    window.onclick = function (event) {
      if (event.target === modal) {
        closeModal();
      }
    };
  </script>
</body>
</html>
