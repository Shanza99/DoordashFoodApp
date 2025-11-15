import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'DoorDash Merchant',
      theme: ThemeData(
        primaryColor: Color(0xFF0066CC),
        primarySwatch: Colors.blue,
        colorScheme: ColorScheme.light(
          primary: Color(0xFF0066CC),
          secondary: Color(0xFF66AADD),
        ),
        visualDensity: VisualDensity.adaptivePlatformDensity,
        appBarTheme: AppBarTheme(
          backgroundColor: Color(0xFF0066CC),
          foregroundColor: Colors.white,
          elevation: 0,
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: Color(0xFF0066CC),
            foregroundColor: Colors.white,
            textStyle: TextStyle(fontWeight: FontWeight.bold),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            padding: EdgeInsets.symmetric(vertical: 12, horizontal: 24),
          ),
        ),
        floatingActionButtonTheme: FloatingActionButtonThemeData(
          backgroundColor: Color(0xFF0066CC),
          foregroundColor: Colors.white,
        ),
        inputDecorationTheme: InputDecorationTheme(
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Colors.grey[400]!),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: Color(0xFF0066CC), width: 2),
          ),
        ),
      ),
      home: LoginScreen(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class AuthService {
  static int? userId;
  static int? restaurantId;
  static String? userName;
  
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      print('Attempting login for: $email');
      var response = await http.post(
        Uri.parse('localhostDoordash/api_simple_login.php'),
        body: {
          'email': email,
          'password': password,
        },
      );

      print('Login response status: ${response.statusCode}');
      print('Login response body: ${response.body}');

      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        return data;
      }
      return {'success': false, 'message': 'Network error: ${response.statusCode}'};
    } catch (e) {
      print('Login error: $e');
      return {'success': false, 'message': 'Connection failed: $e'};
    }
  }
}

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _isLoading = false;
  String _errorMessage = '';

  Future<void> _login() async {
    if (_emailController.text.isEmpty || _passwordController.text.isEmpty) {
      setState(() {
        _errorMessage = 'Please enter email and password';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    var result = await AuthService.login(_emailController.text, _passwordController.text);

    setState(() {
      _isLoading = false;
    });

    if (result['success'] == true) {
      AuthService.userId = result['user_id'] != null ? int.tryParse(result['user_id'].toString()) : null;
      AuthService.restaurantId = result['restaurant_id'] != null ? int.tryParse(result['restaurant_id'].toString()) : null;
      AuthService.userName = result['user_name']?.toString();
      
      print('Login successful - User ID: ${AuthService.userId}, Restaurant ID: ${AuthService.restaurantId}');
      
      if (AuthService.userId != null && AuthService.restaurantId != null) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => DashboardScreen()),
        );
      } else {
        setState(() {
          _errorMessage = 'Login successful but missing user or restaurant data';
        });
      }
    } else {
      setState(() {
        _errorMessage = result['message'] ?? 'Login failed';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Padding(
        padding: EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.restaurant,
              size: 80,
              color: Color(0xFF0066CC),
            ),
            SizedBox(height: 20),
            Text(
              'DoorDash Merchant',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: Color(0xFF0066CC),
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Manage your restaurant',
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey[600],
              ),
            ),
            SizedBox(height: 40),
            
            if (_errorMessage.isNotEmpty)
              Container(
                width: double.infinity,
                padding: EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.red[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.red),
                ),
                child: Text(
                  _errorMessage,
                  style: TextStyle(color: Colors.red),
                  textAlign: TextAlign.center,
                ),
              ),
            
            if (_errorMessage.isNotEmpty) SizedBox(height: 16),
            
            TextField(
              controller: _emailController,
              decoration: InputDecoration(
                labelText: 'Email',
                prefixIcon: Icon(Icons.email, color: Color(0xFF0066CC)),
              ),
            ),
            SizedBox(height: 16),
            TextField(
              controller: _passwordController,
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'Password',
                prefixIcon: Icon(Icons.lock, color: Color(0xFF0066CC)),
              ),
              onSubmitted: (_) => _login(),
            ),
            SizedBox(height: 30),
            _isLoading
                ? CircularProgressIndicator()
                : SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      onPressed: _login,
                      child: Text(
                        'Login',
                        style: TextStyle(fontSize: 18),
                      ),
                    ),
                  ),
                    
            SizedBox(height: 20),
            TextButton(
              onPressed: () {
                _emailController.text = 'bonash@gmail.com';
                _passwordController.text = 'bonash';
              },
              child: Text(
                'Use Test Credentials (Bonash)',
                style: TextStyle(color: Color(0xFF0066CC)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class DashboardScreen extends StatefulWidget {
  @override
  _DashboardScreenState createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _currentIndex = 0;

  final List<Widget> _screens = [
    OrdersScreen(),
    MenuScreen(),
    HistoryScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Merchant Dashboard - ${AuthService.userName ?? "Restaurant"}'),
        backgroundColor: Color(0xFF0066CC),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: Icon(Icons.logout),
            onPressed: () {
              AuthService.userId = null;
              AuthService.restaurantId = null;
              AuthService.userName = null;
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (context) => LoginScreen()),
              );
            },
          ),
        ],
      ),
      body: _screens[_currentIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        selectedItemColor: Color(0xFF0066CC),
        unselectedItemColor: Colors.grey,
        items: [
          BottomNavigationBarItem(
            icon: Icon(Icons.list_alt),
            label: 'Orders',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.restaurant_menu),
            label: 'Menu',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.history),
            label: 'History',
          ),
        ],
      ),
    );
  }
}

class OrdersScreen extends StatefulWidget {
  @override
  _OrdersScreenState createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<dynamic> _newOrders = [];
  List<dynamic> _readyOrders = [];
  List<dynamic> _progressOrders = [];
  bool _isLoading = true;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _loadOrders();
    _refreshTimer = Timer.periodic(Duration(seconds: 10), (timer) {
      _loadOrders();
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _loadOrders() async {
    if (AuthService.restaurantId == null) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
      return;
    }

    try {
      var response = await http.get(
        Uri.parse('localhostDoordash/api_merchant_simple.php?action=get_orders&restaurant_id=${AuthService.restaurantId}'),
      );

      print('Orders response: ${response.body}');

      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        if (data['success'] == true) {
          if (mounted) {
            setState(() {
              _newOrders = data['new_orders'] ?? [];
              _readyOrders = data['ready_orders'] ?? [];
              _progressOrders = data['progress_orders'] ?? [];
              _isLoading = false;
            });
          }
        } else {
          throw Exception(data['message'] ?? 'Failed to load orders');
        }
      } else {
        throw Exception('HTTP error ${response.statusCode}');
      }
    } catch (e) {
      print('Error loading orders: $e');
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _updateOrderStatus(int orderId, String status) async {
    try {
      print('Updating order $orderId to status: $status');
      var response = await http.post(
        Uri.parse('localhostDoordash/api_merchant_simple.php'),
        body: {
          'action': 'update_order_status',
          'restaurant_id': AuthService.restaurantId.toString(),
          'order_id': orderId.toString(),
          'status': status,
        },
      );

      print('Update response: ${response.body}');

      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        if (data['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Order #$orderId status updated to ${status.replaceAll('_', ' ')}'),
              backgroundColor: Colors.green,
            ),
          );
          await _loadOrders();
        } else {
          throw Exception(data['message'] ?? 'Update failed');
        }
      } else {
        throw Exception('HTTP error ${response.statusCode}');
      }
    } catch (e) {
      print('Error updating order status: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Widget _buildOrderCard(dynamic order, String section) {
    int orderId = int.tryParse(order['id'].toString()) ?? 0;
    String status = order['status'].toString();

    return Card(
      margin: EdgeInsets.symmetric(vertical: 8, horizontal: 16),
      elevation: 3,
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Order #$orderId',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                ),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: _getStatusColor(status),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    status.replaceAll('_', ' ').toUpperCase(),
                    style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            SizedBox(height: 12),
            Text('Customer: ${order['customer_name']}', style: TextStyle(fontSize: 16)),
            SizedBox(height: 4),
            Text('Phone: ${order['customer_phone'] ?? 'N/A'}', style: TextStyle(fontSize: 14)),
            SizedBox(height: 4),
            Text('Total: \$${order['total_amount']}', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            SizedBox(height: 4),
            Text('Address: ${order['delivery_address']}', style: TextStyle(fontSize: 14, color: Colors.grey[600])),
            SizedBox(height: 8),
            Text('Placed: ${_formatDate(order['created_at'])}', style: TextStyle(fontSize: 12, color: Colors.grey)),
            SizedBox(height: 16),
            
            if (section == 'new')
              _buildNewOrderActions(orderId, status),
            if (section == 'ready')
              _buildReadyOrderInfo(),
            if (section == 'progress')
              _buildProgressOrderInfo(),
          ],
        ),
      ),
    );
  }

  Widget _buildNewOrderActions(int orderId, String status) {
    return Column(
      children: [
        if (status == 'pending')
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: () => _updateOrderStatus(orderId, 'confirmed'),
                  child: Text('ACCEPT ORDER', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ),
              SizedBox(width: 8),
              Expanded(
                child: ElevatedButton(
                  onPressed: () => _updateOrderStatus(orderId, 'cancelled'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.grey,
                  ),
                  child: Text('CANCEL', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          )
        else if (status == 'confirmed')
          ElevatedButton(
            onPressed: () => _updateOrderStatus(orderId, 'preparing'),
            child: Text('START PREPARING', style: TextStyle(fontWeight: FontWeight.bold)),
          )
        else if (status == 'preparing')
          ElevatedButton(
            onPressed: () => _updateOrderStatus(orderId, 'ready_for_pickup'),
            child: Text('MARK READY FOR PICKUP', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
      ],
    );
  }

  Widget _buildReadyOrderInfo() {
    return Container(
      padding: EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.orange[50],
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Icon(Icons.info, color: Colors.orange),
          SizedBox(width: 8),
          Expanded(
            child: Text(
              'Waiting for delivery driver to pick up',
              style: TextStyle(color: Colors.orange[800]),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProgressOrderInfo() {
    return Container(
      padding: EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.green[50],
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Icon(Icons.delivery_dining, color: Colors.green),
          SizedBox(width: 8),
          Expanded(
            child: Text(
              'Order is on the way to customer',
              style: TextStyle(color: Colors.green[800]),
            ),
          ),
        ],
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'pending': return Colors.orange;
      case 'confirmed': return Color(0xFF0066CC);
      case 'preparing': return Colors.purple;
      case 'ready_for_pickup': return Colors.blue;
      case 'picked_up': return Colors.teal;
      case 'on_the_way': return Colors.indigo;
      case 'delivered': return Colors.green;
      case 'cancelled': return Colors.red;
      default: return Colors.grey;
    }
  }

  String _formatDate(String dateString) {
    try {
      DateTime date = DateTime.parse(dateString);
      return '${date.hour}:${date.minute.toString().padLeft(2, '0')} - ${date.day}/${date.month}/${date.year}';
    } catch (e) {
      return dateString;
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          backgroundColor: Colors.white,
          elevation: 0,
          automaticallyImplyLeading: false,
          title: Text(
            'Orders',
            style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold),
          ),
          bottom: TabBar(
            labelColor: Color(0xFF0066CC),
            unselectedLabelColor: Colors.grey,
            indicatorColor: Color(0xFF0066CC),
            tabs: [
              Tab(text: 'NEW (${_newOrders.length})'),
              Tab(text: 'READY (${_readyOrders.length})'),
              Tab(text: 'PROGRESS (${_progressOrders.length})'),
            ],
          ),
        ),
        body: _isLoading
            ? Center(child: CircularProgressIndicator(color: Color(0xFF0066CC)))
            : RefreshIndicator(
                onRefresh: _loadOrders,
                color: Color(0xFF0066CC),
                child: TabBarView(
                  children: [
                    _newOrders.isEmpty
                        ? _buildEmptyState('No New Orders', 'You don\'t have any new orders at the moment.')
                        : ListView.builder(
                            itemCount: _newOrders.length,
                            itemBuilder: (context, index) => _buildOrderCard(_newOrders[index], 'new'),
                          ),
                    _readyOrders.isEmpty
                        ? _buildEmptyState('No Orders Ready', 'No orders are currently ready for pickup.')
                        : ListView.builder(
                            itemCount: _readyOrders.length,
                            itemBuilder: (context, index) => _buildOrderCard(_readyOrders[index], 'ready'),
                          ),
                    _progressOrders.isEmpty
                        ? _buildEmptyState('No Orders In Progress', 'No orders are currently being delivered.')
                        : ListView.builder(
                            itemCount: _progressOrders.length,
                            itemBuilder: (context, index) => _buildOrderCard(_progressOrders[index], 'progress'),
                          ),
                  ],
                ),
              ),
        floatingActionButton: FloatingActionButton(
          onPressed: _loadOrders,
          child: Icon(Icons.refresh),
          backgroundColor: Color(0xFF0066CC),
        ),
      ),
    );
  }

  Widget _buildEmptyState(String title, String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.list_alt, size: 80, color: Colors.grey[400]),
          SizedBox(height: 16),
          Text(
            title,
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.grey[600]),
          ),
          SizedBox(height: 8),
          Text(
            message,
            style: TextStyle(fontSize: 16, color: Colors.grey[500]),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}
class MenuScreen extends StatefulWidget {
  @override
  _MenuScreenState createState() => _MenuScreenState();
}

class _MenuScreenState extends State<MenuScreen> {
  List<dynamic> _menuItems = [];
  bool _isLoading = true;
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final TextEditingController _priceController = TextEditingController();
  final TextEditingController _categoryController = TextEditingController();
  bool _showForm = false;
  bool _isEditing = false;
  int? _editingItemId;

  @override
  void initState() {
    super.initState();
    _loadMenuItems();
  }

  Future<void> _loadMenuItems() async {
    if (AuthService.restaurantId == null) {
      setState(() { _isLoading = false; });
      return;
    }

    try {
      var response = await http.get(
        Uri.parse('localhostDoordash/api_merchant_simple.php?action=get_menu&restaurant_id=${AuthService.restaurantId}'),
      );

      print('Load menu response: ${response.body}');

      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _menuItems = data['menu_items'] ?? [];
            _isLoading = false;
          });
        } else {
          throw Exception(data['message'] ?? 'Failed to load menu');
        }
      } else {
        throw Exception('HTTP error ${response.statusCode}');
      }
    } catch (e) {
      print('Error loading menu: $e');
      setState(() { _isLoading = false; });
    }
  }

  void _showAddForm() {
    setState(() {
      _showForm = true;
      _isEditing = false;
      _editingItemId = null;
      _clearForm();
    });
  }

  void _hideForm() {
    setState(() {
      _showForm = false;
      _isEditing = false;
      _editingItemId = null;
      _clearForm();
    });
  }

  Future<void> _addMenuItem() async {
    if (_nameController.text.isEmpty || _priceController.text.isEmpty || _categoryController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Please fill all required fields'), backgroundColor: Colors.red),
      );
      return;
    }

    try {
      print('Adding menu item: ${_nameController.text}, ${_priceController.text}, ${_categoryController.text}');
      
      var response = await http.post(
        Uri.parse('localhostDoordash/api_merchant_simple.php'),
        body: {
          'action': 'add_menu_item',
          'restaurant_id': AuthService.restaurantId.toString(),
          'name': _nameController.text,
          'description': _descriptionController.text,
          'price': _priceController.text,
          'category': _categoryController.text,
        },
      );

      print('Add menu item response: ${response.body}');

      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        if (data['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Menu item added successfully!'), backgroundColor: Colors.green),
          );
          _hideForm();
          await _loadMenuItems();
        } else {
          throw Exception(data['message'] ?? 'Failed to add menu item');
        }
      } else {
        throw Exception('HTTP error ${response.statusCode}');
      }
    } catch (e) {
      print('Error adding menu item: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  Future<void> _updateMenuItem() async {
    if (_nameController.text.isEmpty || _priceController.text.isEmpty || _categoryController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Please fill all required fields'), backgroundColor: Colors.red),
      );
      return;
    }

    try {
      var response = await http.post(
        Uri.parse('localhostDoordash/api_merchant_simple.php'),
        body: {
          'action': 'update_menu_item',
          'restaurant_id': AuthService.restaurantId.toString(),
          'id': _editingItemId.toString(),
          'name': _nameController.text,
          'description': _descriptionController.text,
          'price': _priceController.text,
          'category': _categoryController.text,
        },
      );

      print('Update menu item response: ${response.body}');

      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        if (data['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Menu item updated successfully!'), backgroundColor: Colors.green),
          );
          _hideForm();
          await _loadMenuItems();
        } else {
          throw Exception(data['message'] ?? 'Failed to update menu item');
        }
      } else {
        throw Exception('HTTP error ${response.statusCode}');
      }
    } catch (e) {
      print('Error updating menu item: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  Future<void> _deleteMenuItem(int itemId) async {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Text('Confirm Delete'),
          content: Text('Are you sure you want to delete this menu item?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: Text('Cancel'),
            ),
            TextButton(
              onPressed: () async {
                Navigator.of(context).pop();
                await _performDelete(itemId);
              },
              child: Text('Delete', style: TextStyle(color: Colors.red)),
            ),
          ],
        );
      },
    );
  }

  Future<void> _performDelete(int itemId) async {
    try {
      var response = await http.post(
        Uri.parse('localhostDoordash/api_merchant_simple.php'),
        body: {
          'action': 'delete_menu_item',
          'restaurant_id': AuthService.restaurantId.toString(),
          'id': itemId.toString(),
        },
      );

      print('Delete menu item response: ${response.body}');

      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        if (data['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Menu item deleted successfully!'), backgroundColor: Colors.green),
          );
          await _loadMenuItems();
        } else {
          throw Exception(data['message'] ?? 'Failed to delete menu item');
        }
      } else {
        throw Exception('HTTP error ${response.statusCode}');
      }
    } catch (e) {
      print('Error deleting menu item: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
  }

  void _clearForm() {
    _nameController.clear();
    _descriptionController.clear();
    _priceController.clear();
    _categoryController.clear();
  }

  void _editMenuItem(dynamic item) {
    int itemId = int.tryParse(item['id'].toString()) ?? 0;
    
    setState(() {
      _showForm = true;
      _isEditing = true;
      _editingItemId = itemId;
      _nameController.text = item['name']?.toString() ?? '';
      _descriptionController.text = item['description']?.toString() ?? '';
      _priceController.text = item['price']?.toString() ?? '';
      _categoryController.text = item['category']?.toString() ?? '';
    });
  }

  Widget _buildMenuForm() {
    return Card(
      margin: EdgeInsets.all(16),
      elevation: 3,
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  _isEditing ? 'Edit Menu Item' : 'Add New Menu Item',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0066CC)),
                ),
                IconButton(
                  icon: Icon(Icons.close, color: Colors.grey),
                  onPressed: _hideForm,
                ),
              ],
            ),
            SizedBox(height: 16),
            TextField(
              controller: _nameController,
              decoration: InputDecoration(
                labelText: 'Item Name *',
                prefixIcon: Icon(Icons.fastfood, color: Color(0xFF0066CC)),
              ),
            ),
            SizedBox(height: 12),
            TextField(
              controller: _descriptionController,
              decoration: InputDecoration(
                labelText: 'Description',
                prefixIcon: Icon(Icons.description, color: Color(0xFF0066CC)),
              ),
            ),
            SizedBox(height: 12),
            TextField(
              controller: _priceController,
              decoration: InputDecoration(
                labelText: 'Price *',
                prefixText: '\$',
                prefixIcon: Icon(Icons.attach_money, color: Color(0xFF0066CC)),
              ),
              keyboardType: TextInputType.numberWithOptions(decimal: true),
            ),
            SizedBox(height: 12),
            TextField(
              controller: _categoryController,
              decoration: InputDecoration(
                labelText: 'Category *',
                prefixIcon: Icon(Icons.category, color: Color(0xFF0066CC)),
              ),
            ),
            SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: _isEditing ? _updateMenuItem : _addMenuItem,
                    child: Text(
                      _isEditing ? 'UPDATE ITEM' : 'ADD ITEM',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
                SizedBox(width: 8),
                ElevatedButton(
                  onPressed: _hideForm,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.grey,
                  ),
                  child: Text(
                    'CANCEL',
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMenuItemCard(dynamic item) {
    int itemId = int.tryParse(item['id'].toString()) ?? 0;
    
    return Card(
      margin: EdgeInsets.symmetric(vertical: 8, horizontal: 16),
      elevation: 2,
      child: ListTile(
        contentPadding: EdgeInsets.all(16),
        leading: Icon(Icons.restaurant, color: Color(0xFF0066CC), size: 30),
        title: Text(
          item['name']?.toString() ?? 'Unnamed Item',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (item['description'] != null && item['description'].toString().isNotEmpty)
              Text(item['description'].toString(), style: TextStyle(fontSize: 14)),
            SizedBox(height: 4),
            Row(
              children: [
                Text(
                  '\$${item['price']}',
                  style: TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF0066CC)),
                ),
                SizedBox(width: 8),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: Color(0xFF0066CC).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(4),
                    border: Border.all(color: Color(0xFF0066CC).withOpacity(0.3)),
                  ),
                  child: Text(
                    item['category']?.toString() ?? 'Uncategorized',
                    style: TextStyle(fontSize: 12, color: Color(0xFF0066CC)),
                  ),
                ),
              ],
            ),
          ],
        ),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            IconButton(
              icon: Icon(Icons.edit, color: Color(0xFF0066CC)),
              onPressed: () => _editMenuItem(item),
            ),
            IconButton(
              icon: Icon(Icons.delete, color: Colors.red),
              onPressed: () => _deleteMenuItem(itemId),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: Color(0xFF0066CC)))
          : Column(
              children: [
                if (_showForm) _buildMenuForm(),
                if (_menuItems.isNotEmpty && !_showForm)
                  Padding(
                    padding: EdgeInsets.all(16),
                    child: Text(
                      'Menu Items (${_menuItems.length})',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0066CC)),
                    ),
                  ),
                Expanded(
                  child: _menuItems.isEmpty && !_showForm
                      ? _buildEmptyState('No Menu Items', 'Tap the + button to add your first menu item.')
                      : ListView.builder(
                          itemCount: _menuItems.length,
                          itemBuilder: (context, index) => _buildMenuItemCard(_menuItems[index]),
                        ),
                ),
              ],
            ),
      floatingActionButton: !_showForm
          ? FloatingActionButton(
              onPressed: _showAddForm,
              child: Icon(Icons.add),
              backgroundColor: Color(0xFF0066CC),
            )
          : null,
    );
  }

  Widget _buildEmptyState(String title, String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.restaurant_menu, size: 80, color: Colors.grey[400]),
          SizedBox(height: 16),
          Text(
            title,
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.grey[600]),
          ),
          SizedBox(height: 8),
          Text(
            message,
            style: TextStyle(fontSize: 16, color: Colors.grey[500]),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class HistoryScreen extends StatefulWidget {
  @override
  _HistoryScreenState createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<dynamic> _completedOrders = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    if (AuthService.restaurantId == null) {
      setState(() { _isLoading = false; });
      return;
    }

    try {
      var response = await http.get(
        Uri.parse('localhostDoordash/api_merchant_simple.php?action=get_history&restaurant_id=${AuthService.restaurantId}'),
      );

      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _completedOrders = data['completed_orders'] ?? [];
            _isLoading = false;
          });
        } else {
          throw Exception(data['message'] ?? 'Failed to load history');
        }
      } else {
        throw Exception('HTTP error ${response.statusCode}');
      }
    } catch (e) {
      setState(() { _isLoading = false; });
    }
  }

  Widget _buildHistoryCard(dynamic order) {
    int orderId = int.tryParse(order['id'].toString()) ?? 0;
    
    return Card(
      margin: EdgeInsets.symmetric(vertical: 8, horizontal: 16),
      elevation: 3,
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Order #$orderId',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                ),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: order['status'] == 'delivered' ? Colors.green : Colors.red,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    order['status'].toString().toUpperCase(),
                    style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            SizedBox(height: 12),
            Text('Customer: ${order['customer_name']}', style: TextStyle(fontSize: 16)),
            SizedBox(height: 4),
            Text('Delivery Person: ${order['delivery_person_name'] ?? 'N/A'}', style: TextStyle(fontSize: 14)),
            SizedBox(height: 4),
            Text('Total: \$${order['total_amount']}', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Color(0xFF0066CC))),
            SizedBox(height: 4),
            Text('Address: ${order['delivery_address']}', style: TextStyle(fontSize: 14, color: Colors.grey[600])),
            SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Placed: ${_formatDate(order['created_at'])}', style: TextStyle(fontSize: 12, color: Colors.grey)),
                Text('Completed: ${_formatDate(order['updated_at'])}', style: TextStyle(fontSize: 12, color: Colors.grey)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(String dateString) {
    try {
      DateTime date = DateTime.parse(dateString);
      return '${date.hour}:${date.minute.toString().padLeft(2, '0')} - ${date.day}/${date.month}/${date.year}';
    } catch (e) {
      return dateString;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: Color(0xFF0066CC)))
          : RefreshIndicator(
              onRefresh: _loadHistory,
              color: Color(0xFF0066CC),
              child: _completedOrders.isEmpty
                  ? _buildEmptyState('No Order History', 'Your completed and cancelled orders will appear here.')
                  : ListView.builder(
                      itemCount: _completedOrders.length,
                      itemBuilder: (context, index) => _buildHistoryCard(_completedOrders[index]),
                    ),
            ),
    );
  }

  Widget _buildEmptyState(String title, String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.history, size: 80, color: Colors.grey[400]),
          SizedBox(height: 16),
          Text(
            title,
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.grey[600]),
          ),
          SizedBox(height: 8),
          Text(
            message,
            style: TextStyle(fontSize: 16, color: Colors.grey[500]),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}