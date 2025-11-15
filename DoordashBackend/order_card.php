<?php
// This file is included in customer_dashboard.php to display individual orders
?>
<div class="order-card">
    <div class="order-header">
        <div class="order-restaurant">
            <i class="fas fa-utensils"></i>
            <?php echo htmlspecialchars($order['restaurant_name']); ?>
        </div>
        <div class="order-meta">
            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
            <span><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></span>
        </div>
        <div class="order-status status-<?php echo $order['status']; ?>">
            <?php echo str_replace('_', ' ', $order['status']); ?>
        </div>
    </div>
    
    <div class="order-body">
        <div class="order-items">
            <?php foreach($order['items'] as $item): ?>
                <div class="order-item">
                    <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                    <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                    <div class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="order-total">
            <span>Total Amount:</span>
            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
        
        <?php if($order['delivery_person_name']): ?>
            <div style="margin-top: 10px; color: #666; font-size: 14px;">
                <i class="fas fa-motorcycle"></i> 
                Delivered by: <?php echo htmlspecialchars($order['delivery_person_name']); ?>
            </div>
        <?php endif; ?>
        
        <?php if($order['instructions']): ?>
            <div style="margin-top: 10px; color: #666; font-size: 14px;">
                <i class="fas fa-sticky-note"></i> 
                Instructions: <?php echo htmlspecialchars($order['instructions']); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="order-actions">
        <a href="restaurant_menu.php?id=<?php echo $order['restaurant_id']; ?>" class="action-btn btn-primary">
            <i class="fas fa-utensils"></i> Order Again
        </a>
        <?php if(in_array($order['status'], ['pending', 'confirmed', 'preparing'])): ?>
            <button class="action-btn btn-secondary" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                <i class="fas fa-times"></i> Cancel Order
            </button>
        <?php endif; ?>
    </div>
</div>