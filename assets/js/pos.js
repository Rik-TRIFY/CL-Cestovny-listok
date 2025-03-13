document.addEventListener('DOMContentLoaded', function() {
    const cart = {
        items: {},
        
        addItem(id, name, price) {
            if (!this.items[id]) {
                this.items[id] = { id, name, price, qty: 0 };
            }
            this.items[id].qty++;
            this.updateDisplay();
        },
        
        removeItem(id) {
            if (this.items[id] && this.items[id].qty > 0) {
                this.items[id].qty--;
                if (this.items[id].qty === 0) {
                    delete this.items[id];
                }
                this.updateDisplay();
            }
        },
        
        updateDisplay() {
            const container = document.querySelector('.pos-cart-items');
            container.innerHTML = '';
            
            Object.values(this.items).forEach(item => {
                container.innerHTML += `
                    <div class="pos-cart-item">
                        <div class="pos-item-info">
                            <div class="pos-item-name">${item.name}</div>
                            <div class="pos-item-price">${item.price.toFixed(2)} €</div>
                        </div>
                        <div class="pos-item-controls">
                            <button class="pos-qty-btn" onclick="cart.removeItem(${item.id})">-</button>
                            <span class="pos-item-qty">${item.qty}</span>
                            <button class="pos-qty-btn" onclick="cart.addItem(${item.id}, '${item.name}', ${item.price})">+</button>
                        </div>
                    </div>
                `;
            });
            
            const total = Object.values(this.items).reduce((sum, item) => sum + (item.price * item.qty), 0);
            document.querySelector('.pos-total-amount').textContent = total.toFixed(2) + ' €';
        }
    };

    // Přidání do globálního scope pro použití v onclick
    window.cart = cart;

    // Event Listeners
    document.querySelectorAll('.pos-product').forEach(product => {
        product.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            const name = this.querySelector('.pos-product-name').textContent;
            const price = parseFloat(this.querySelector('.pos-product-price').textContent);
            cart.addItem(id, name, price);
        });
    });

    document.querySelector('.pos-cart-toggle')?.addEventListener('click', () => {
        document.querySelector('.pos-cart').classList.toggle('active');
    });

    // Dokončení objednávky
    document.querySelector('.pos-checkout')?.addEventListener('click', function() {
        if (Object.keys(cart.items).length === 0) {
            alert('Košík je prázdny');
            return;
        }

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'cl_dokoncit_predaj',
                nonce: cl_pos.nonce,
                items: JSON.stringify(cart.items)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Vyčistíme košík
                cart.items = {};
                cart.updateDisplay();
                
                // Otevřeme okno pro tisk
                const printWindow = window.open('', 'PRINT', 'height=600,width=800');
                printWindow.document.write(data.html);
                printWindow.document.close();
                printWindow.focus();
                
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            } else {
                alert('Chyba: ' + data.data);
            }
        });
    });
});
