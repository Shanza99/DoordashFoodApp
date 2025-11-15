import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import 'dart:typed_data';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'DoorDash Dasher',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        colorScheme: ColorScheme.light(
          primary: Color(0xFF87CEEB),
          secondary: Color(0xFF4682B4),
          onPrimary: Colors.white,
        ),
        fontFamily: 'Roboto',
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: Color(0xFF87CEEB),
            foregroundColor: Colors.white,
          ),
        ),
        textButtonTheme: TextButtonThemeData(
          style: TextButton.styleFrom(
            foregroundColor: Color(0xFF4682B4),
          ),
        ),
        floatingActionButtonTheme: FloatingActionButtonThemeData(
          backgroundColor: Color(0xFF87CEEB),
          foregroundColor: Colors.white,
        ),
        appBarTheme: AppBarTheme(
          backgroundColor: Color(0xFF87CEEB),
          foregroundColor: Colors.white,
        ),
        bottomNavigationBarTheme: BottomNavigationBarThemeData(
          selectedItemColor: Color(0xFF4682B4),
          unselectedItemColor: Colors.grey,
        ),
      ),
      home: SplashScreen(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class SplashScreen extends StatefulWidget {
  @override
  _SplashScreenState createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  final storage = FlutterSecureStorage();

  @override
  void initState() {
    super.initState();
    _checkLoginStatus();
  }

  _checkLoginStatus() async {
    try {
      final token = await storage.read(key: 'user_token');
      final userData = await storage.read(key: 'user_data');
      
      await Future.delayed(Duration(seconds: 2));
      
      if (token != null && userData != null) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => DashboardScreen()),
        );
      } else {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => LoginScreen()),
        );
      }
    } catch (e) {
      print('Error checking login status: $e');
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => LoginScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Color(0xFF87CEEB),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.motorcycle,
              size: 80,
              color: Colors.white,
            ),
            SizedBox(height: 20),
            Text(
              'DoorDash Dasher',
              style: TextStyle(
                color: Colors.white,
                fontSize: 28,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 10),
            CircularProgressIndicator(
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ],
        ),
      ),
    );
  }
}

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final storage = FlutterSecureStorage();
  bool _isLoading = false;
  bool _obscurePassword = true;

  Future<void> _login() async {
    if (_emailController.text.isEmpty || _passwordController.text.isEmpty) {
      _showError('Please fill in all fields');
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.post(
        Uri.parse('localhostDoordash/delivery_login.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json',
        },
        body: {
          'email': _emailController.text,
          'password': _passwordController.text,
        },
      );

      print('Login Response: ${response.body}');

      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        Map<String, dynamic> userData = {
          'id': data['user']['id'] ?? 0,
          'name': data['user']['name'] ?? '',
          'email': data['user']['email'] ?? '',
          'delivery_person_id': data['user']['delivery_person_id'] ?? 0,
        };
        
        await storage.write(key: 'user_token', value: 'authenticated');
        await storage.write(key: 'user_data', value: json.encode(userData));
        
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => DashboardScreen()),
        );
      } else {
        _showError(data['message'] ?? 'Login failed');
      }
    } catch (e) {
      print('Login error: $e');
      _showError('Network error: Please check your connection');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SingleChildScrollView(
        child: Container(
          height: MediaQuery.of(context).size.height,
          child: Column(
            children: [
              Container(
                height: 250,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFF87CEEB), Color(0xFF4682B4)],
                  ),
                  borderRadius: BorderRadius.only(
                    bottomLeft: Radius.circular(30),
                    bottomRight: Radius.circular(30),
                  ),
                ),
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.motorcycle,
                        size: 60,
                        color: Colors.white,
                      ),
                      SizedBox(height: 15),
                      Text(
                        'Dasher Login',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 28,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 8),
                      Text(
                        'Access your delivery dashboard',
                        style: TextStyle(
                          color: Colors.white70,
                          fontSize: 16,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Expanded(
                child: Padding(
                  padding: EdgeInsets.all(30),
                  child: Column(
                    children: [
                      TextField(
                        controller: _emailController,
                        decoration: InputDecoration(
                          labelText: 'Email Address',
                          prefixIcon: Icon(Icons.email, color: Color(0xFF4682B4)),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          filled: true,
                          fillColor: Colors.grey[50],
                        ),
                        keyboardType: TextInputType.emailAddress,
                      ),
                      SizedBox(height: 20),
                      TextField(
                        controller: _passwordController,
                        decoration: InputDecoration(
                          labelText: 'Password',
                          prefixIcon: Icon(Icons.lock, color: Color(0xFF4682B4)),
                          suffixIcon: IconButton(
                            icon: Icon(
                              _obscurePassword
                                  ? Icons.visibility
                                  : Icons.visibility_off,
                              color: Color(0xFF4682B4),
                            ),
                            onPressed: () {
                              setState(() {
                                _obscurePassword = !_obscurePassword;
                              });
                            },
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          filled: true,
                          fillColor: Colors.grey[50],
                        ),
                        obscureText: _obscurePassword,
                      ),
                      SizedBox(height: 30),
                      Container(
                        width: double.infinity,
                        height: 55,
                        child: ElevatedButton(
                          onPressed: _isLoading ? null : _login,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Color(0xFF87CEEB),
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 3,
                          ),
                          child: _isLoading
                              ? SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    valueColor: AlwaysStoppedAnimation(Colors.white),
                                  ),
                                )
                              : Text(
                                  'LOGIN TO DASHBOARD',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                  ),
                                ),
                        ),
                      ),
                      SizedBox(height: 20),
                      Text(
                        'Contact support: support@doordash.com',
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
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
  final storage = FlutterSecureStorage();
  int _currentIndex = 0;
  Map<String, dynamic>? _userData;
  Map<String, dynamic>? _deliveryStats;

  List<Widget> _screens = [];

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    final userDataString = await storage.read(key: 'user_data');
    if (userDataString != null) {
      setState(() {
        _userData = json.decode(userDataString);
      });
      _loadDeliveryStats();
    }
    
    WidgetsBinding.instance.addPostFrameCallback((_) {
      setState(() {
        _screens = [
          OverviewTab(userData: _userData),
          AvailableOrdersTab(userData: _userData),
          ActiveOrdersTab(userData: _userData),
          OrderHistoryTab(userData: _userData),
        ];
      });
    });
  }

  Future<void> _loadDeliveryStats() async {
    if (_userData == null) return;
    
    try {
      final response = await http.get(
        Uri.parse('localhostDoordash/get_delivery_stats.php?user_id=${_userData!['id']}'),
        headers: {'Accept': 'application/json'},
      );

      print('Stats Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _deliveryStats = data['stats'];
          });
        }
      }
    } catch (e) {
      print('Error loading stats: $e');
    }
  }

  Future<void> _logout() async {
    await storage.delete(key: 'user_token');
    await storage.delete(key: 'user_data');
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (context) => LoginScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Dasher Dashboard',
          style: TextStyle(color: Colors.white),
        ),
        backgroundColor: Color(0xFF87CEEB),
        elevation: 0,
        actions: [
          IconButton(
            icon: Icon(Icons.refresh, color: Colors.white),
            onPressed: _loadDeliveryStats,
            tooltip: 'Refresh',
          ),
          IconButton(
            icon: Icon(Icons.logout, color: Colors.white),
            onPressed: _logout,
          ),
        ],
      ),
      body: _screens.isNotEmpty 
          ? IndexedStack(
              index: _currentIndex,
              children: _screens,
            )
          : Center(child: CircularProgressIndicator()),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        selectedItemColor: Color(0xFF4682B4),
        unselectedItemColor: Colors.grey,
        items: [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard),
            label: 'Overview',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.local_shipping),
            label: 'Available',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.inventory),
            label: 'Active',
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

class OverviewTab extends StatefulWidget {
  final Map<String, dynamic>? userData;

  const OverviewTab({Key? key, this.userData}) : super(key: key);

  @override
  _OverviewTabState createState() => _OverviewTabState();
}

class _OverviewTabState extends State<OverviewTab> {
  Map<String, dynamic>? _stats;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadStats();
  }

  Future<void> _loadStats() async {
    if (widget.userData == null) return;
    
    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.get(
        Uri.parse('localhostDoordash/get_delivery_stats.php?user_id=${widget.userData!['id']}'),
        headers: {'Accept': 'application/json'},
      );

      print('Overview Stats: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _stats = data['stats'];
          });
        }
      }
    } catch (e) {
      print('Error loading overview stats: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  String _formatAmount(dynamic amount) {
    if (amount == null) return '0.00';
    try {
      double value = double.tryParse(amount.toString()) ?? 0.0;
      return value.toStringAsFixed(2);
    } catch (e) {
      return '0.00';
    }
  }

  String _formatNumber(dynamic number) {
    if (number == null) return '0';
    try {
      return number.toString();
    } catch (e) {
      return '0';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionButton(
        onPressed: _loadStats,
        child: Icon(Icons.refresh, color: Colors.white),
        backgroundColor: Color(0xFF87CEEB),
        tooltip: 'Refresh Stats',
      ),
      body: RefreshIndicator(
        onRefresh: _loadStats,
        child: _isLoading
            ? Center(child: CircularProgressIndicator())
            : SingleChildScrollView(
                padding: EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          'Delivery Overview',
                          style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                        ),
                        Spacer(),
                        TextButton.icon(
                          onPressed: _loadStats,
                          icon: Icon(Icons.refresh, size: 16, color: Color(0xFF4682B4)),
                          label: Text('Try refresh for updates', style: TextStyle(color: Color(0xFF4682B4))),
                        ),
                      ],
                    ),
                    SizedBox(height: 20),
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: NeverScrollableScrollPhysics(),
                      crossAxisSpacing: 16,
                      mainAxisSpacing: 16,
                      children: [
                        _StatCard(
                          value: _formatNumber(_stats?['total_deliveries']),
                          label: 'Total Deliveries',
                          icon: Icons.local_shipping,
                        ),
                        _StatCard(
                          value: '\$${_formatAmount(_stats?['earnings'])}',
                          label: 'Total Earnings',
                          icon: Icons.attach_money,
                        ),
                        _StatCard(
                          value: _formatNumber(_stats?['active_orders']),
                          label: 'Active Deliveries',
                          icon: Icons.inventory,
                        ),
                        _StatCard(
                          value: _formatNumber(_stats?['rating'] ?? '5.0'),
                          label: 'Customer Rating',
                          icon: Icons.star,
                        ),
                      ],
                    ),
                    SizedBox(height: 30),
                    Container(
                      padding: EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.grey[50],
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Quick Actions',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                          ),
                          SizedBox(height: 15),
                          Row(
                            children: [
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () {
                                    final dashboardState = context.findAncestorStateOfType<_DashboardScreenState>();
                                    dashboardState?.setState(() {
                                      dashboardState._currentIndex = 1;
                                    });
                                  },
                                  icon: Icon(Icons.search, color: Colors.white),
                                  label: Text('Find Orders', style: TextStyle(color: Colors.white)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Color(0xFF87CEEB),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String value;
  final String label;
  final IconData icon;

  const _StatCard({
    required this.value,
    required this.label,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black12,
            blurRadius: 4,
            offset: Offset(0, 2),
          ),
        ],
        border: Border(left: BorderSide(color: Color(0xFF87CEEB), width: 4)),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 32, color: Color(0xFF87CEEB)),
          SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF4682B4),
            ),
          ),
          SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
              fontWeight: FontWeight.w500,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class AvailableOrdersTab extends StatefulWidget {
  final Map<String, dynamic>? userData;

  const AvailableOrdersTab({Key? key, this.userData}) : super(key: key);

  @override
  _AvailableOrdersTabState createState() => _AvailableOrdersTabState();
}

class _AvailableOrdersTabState extends State<AvailableOrdersTab> {
  List<dynamic> _availableOrders = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadAvailableOrders();
  }

  Future<void> _loadAvailableOrders() async {
    if (widget.userData == null) {
      print('No user data available');
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.get(
        Uri.parse('localhostDoordash/get_delivery_orders.php?user_id=${widget.userData!['id']}'),
        headers: {'Accept': 'application/json'},
      );

      print('Available Orders Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _availableOrders = data['orders'] ?? [];
          });
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['message'] ?? 'Failed to load orders'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      print('Error loading orders: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Try refresh for updates'),
          backgroundColor: Colors.orange,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _acceptOrder(int orderId) async {
    if (widget.userData == null) return;
    
    try {
      final response = await http.post(
        Uri.parse('localhostDoordash/accept_delivery_order.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json',
        },
        body: {
          'user_id': widget.userData!['id'].toString(),
          'order_id': orderId.toString(),
        },
      );

      print('Accept Order Response: ${response.body}');

      final data = json.decode(response.body);
      if (data['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Order accepted successfully!'),
            backgroundColor: Colors.green,
          ),
        );
        _loadAvailableOrders();
        
        final dashboardState = context.findAncestorStateOfType<_DashboardScreenState>();
        dashboardState?.setState(() {
          dashboardState._currentIndex = 2;
        });
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(data['message'] ?? 'Failed to accept order'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      print('Accept order error: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Try refresh for updates'),
          backgroundColor: Colors.orange,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionButton(
        onPressed: _loadAvailableOrders,
        child: Icon(Icons.refresh, color: Colors.white),
        backgroundColor: Color(0xFF87CEEB),
        tooltip: 'Refresh Orders',
      ),
      body: RefreshIndicator(
        onRefresh: _loadAvailableOrders,
        child: _isLoading
            ? Center(child: CircularProgressIndicator())
            : _availableOrders.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.search, size: 64, color: Colors.grey),
                        SizedBox(height: 16),
                        Text(
                          'No Available Orders',
                          style: TextStyle(fontSize: 18, color: Colors.grey),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'There are no orders available for delivery',
                          style: TextStyle(color: Colors.grey),
                        ),
                        SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: _loadAvailableOrders,
                          icon: Icon(Icons.refresh, color: Colors.white),
                          label: Text('Try refresh for updates', style: TextStyle(color: Colors.white)),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Color(0xFF87CEEB),
                          ),
                        ),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: EdgeInsets.all(16),
                    itemCount: _availableOrders.length,
                    itemBuilder: (context, index) {
                      final order = _availableOrders[index];
                      return _OrderCard(
                        order: order,
                        onAccept: () => _acceptOrder(int.parse(order['id'].toString())),
                        showAcceptButton: true,
                        showStatusActions: false,
                      );
                    },
                  ),
      ),
    );
  }
}

class ActiveOrdersTab extends StatefulWidget {
  final Map<String, dynamic>? userData;

  const ActiveOrdersTab({Key? key, this.userData}) : super(key: key);

  @override
  _ActiveOrdersTabState createState() => _ActiveOrdersTabState();
}

class _ActiveOrdersTabState extends State<ActiveOrdersTab> {
  List<dynamic> _activeOrders = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadActiveOrders();
  }

  Future<void> _loadActiveOrders() async {
    if (widget.userData == null) {
      print('No user data available');
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final deliveryPersonId = widget.userData!['delivery_person_id'] ?? widget.userData!['id'];
      print('Loading orders for delivery person: $deliveryPersonId');
      
      final response = await http.get(
        Uri.parse('localhostDoordash/get_active_orders.php?delivery_person_id=$deliveryPersonId'),
        headers: {'Accept': 'application/json'},
      ).timeout(Duration(seconds: 10));

      print('Active Orders HTTP Status: ${response.statusCode}');
      print('Active Orders Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _activeOrders = data['orders'] ?? [];
          });
          print('Loaded ${_activeOrders.length} active orders');
        } else {
          print('Active Orders API error: ${data['message']}');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['message'] ?? 'Failed to load orders'),
              backgroundColor: Colors.red,
            ),
          );
        }
      } else {
        print('Active Orders HTTP error: ${response.statusCode}');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Server error: ${response.statusCode}. Please try again.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      print('Error loading active orders: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Network error: Please check your connection'),
          backgroundColor: Colors.orange,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _calculateDistance(int orderId) async {
    try {
      final response = await http.post(
        Uri.parse('localhostDoordash/calculate_distance.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
          'order_id': orderId.toString(),
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          print('Distance calculated: ${data['distance']} miles for order $orderId');
        }
      }
    } catch (e) {
      print('Error calculating distance: $e');
    }
  }

  Future<void> _updateOrderStatus(int orderId, String status, {XFile? deliveryProof}) async {
    if (widget.userData == null) return;
    
    try {
      setState(() {
        _isLoading = true;
      });

      if (deliveryProof != null) {
        await _uploadWithBase64(orderId, status, deliveryProof);
      } else {
        await _updateStatusWithoutFile(orderId, status);
      }
    } catch (e) {
      print('Update status error: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to update status. Please try again.'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _updateStatusWithoutFile(int orderId, String status) async {
    try {
      final response = await http.post(
        Uri.parse('localhostDoordash/update_order_status.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
          'user_id': widget.userData!['id'].toString(),
          'order_id': orderId.toString(),
          'status': status,
        },
      ).timeout(Duration(seconds: 10));

      print('Update Status Response: ${response.statusCode} - ${response.body}');

      // Handle the response
      String responseBody = response.body;
      if (responseBody.trim().startsWith('<')) {
        // Response contains HTML error, extract JSON part if any
        final jsonMatch = RegExp(r'\{.*\}').firstMatch(responseBody);
        if (jsonMatch != null) {
          responseBody = jsonMatch.group(0)!;
        }
      }

      final data = json.decode(responseBody);
      if (data['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Order status updated to ${status.replaceAll('_', ' ')}'),
            backgroundColor: Colors.green,
          ),
        );
        await _loadActiveOrders();
      } else {
        throw Exception(data['message'] ?? 'Failed to update status');
      }
    } catch (e) {
      print('Update status without file error: $e');
      rethrow;
    }
  }

  Future<void> _uploadWithBase64(int orderId, String status, XFile deliveryProof) async {
    try {
      final bytes = await deliveryProof.readAsBytes();
      final base64Image = base64Encode(bytes);
      
      print('Uploading image, size: ${bytes.length} bytes');
      
      final response = await http.post(
        Uri.parse('localhostDoordash/update_order_status.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
          'user_id': widget.userData!['id'].toString(),
          'order_id': orderId.toString(),
          'status': status,
          'delivery_proof_base64': base64Image,
        },
      ).timeout(Duration(seconds: 30));

      print('Update Status Response: ${response.statusCode} - ${response.body}');

      // Handle the response - check if it's valid JSON
      String responseBody = response.body;
      if (responseBody.trim().startsWith('<')) {
        // Response contains HTML error, extract JSON part if any
        final jsonMatch = RegExp(r'\{.*\}').firstMatch(responseBody);
        if (jsonMatch != null) {
          responseBody = jsonMatch.group(0)!;
        }
      }

      final data = json.decode(responseBody);
      if (data['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Order delivered successfully!'),
            backgroundColor: Colors.green,
          ),
        );
        await _loadActiveOrders();
      } else {
        throw Exception(data['message'] ?? 'Failed to update status with photo');
      }
    } catch (e) {
      print('Base64 upload error: $e');
      // Don't fallback - let the user know the upload failed
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Photo upload failed, but order was marked as delivered'),
          backgroundColor: Colors.orange,
        ),
      );
      // Still reload orders to reflect any changes
      await _loadActiveOrders();
    }
  }

  void _showDeliveryProofDialog(int orderId) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return DeliveryProofDialog(
          onPhotoTaken: (XFile image) {
            _updateOrderStatus(orderId, 'delivered', deliveryProof: image);
            Navigator.of(context).pop();
          },
        );
      },
    );
  }

  String _getNextAction(String currentStatus) {
    switch (currentStatus) {
      case 'picked_up':
        return 'Start Delivery';
      case 'on_the_way':
        return 'Mark Delivered';
      default:
        return 'Update Status';
    }
  }

  String _getNextStatus(String currentStatus) {
    switch (currentStatus) {
      case 'picked_up':
        return 'on_the_way';
      case 'on_the_way':
        return 'delivered';
      default:
        return currentStatus;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionButton(
        onPressed: _loadActiveOrders,
        child: Icon(Icons.refresh, color: Colors.white),
        backgroundColor: Color(0xFF87CEEB),
        tooltip: 'Refresh Orders',
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : _activeOrders.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.inventory, size: 64, color: Colors.grey),
                      SizedBox(height: 16),
                      Text(
                        'No Active Orders',
                        style: TextStyle(fontSize: 18, color: Colors.grey),
                      ),
                      SizedBox(height: 8),
                      Text(
                        'You don\'t have any active deliveries',
                        style: TextStyle(color: Colors.grey),
                      ),
                      SizedBox(height: 16),
                      ElevatedButton.icon(
                        onPressed: _loadActiveOrders,
                        icon: Icon(Icons.refresh, color: Colors.white),
                        label: Text('Refresh Orders', style: TextStyle(color: Colors.white)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Color(0xFF87CEEB),
                        ),
                      ),
                    ],
                  ),
                )
              : ListView.builder(
                  padding: EdgeInsets.all(16),
                  itemCount: _activeOrders.length,
                  itemBuilder: (context, index) {
                    final order = _activeOrders[index];
                    return _OrderCard(
                      order: order,
                      onAccept: null,
                      showAcceptButton: false,
                      showStatusActions: true,
                      onUpdateStatus: (status) {
                        if (status == 'delivered') {
                          _showDeliveryProofDialog(int.parse(order['id'].toString()));
                        } else {
                          _updateOrderStatus(int.parse(order['id'].toString()), status);
                        }
                      },
                      nextAction: _getNextAction(order['status']),
                      nextStatus: _getNextStatus(order['status']),
                      hasDeliveryProof: order['delivery_proof_image'] != null,
                    );
                  },
                ),
    );
  }
}
class DeliveryProofDialog extends StatefulWidget {
  final Function(XFile) onPhotoTaken;

  const DeliveryProofDialog({Key? key, required this.onPhotoTaken}) : super(key: key);

  @override
  _DeliveryProofDialogState createState() => _DeliveryProofDialogState();
}

class _DeliveryProofDialogState extends State<DeliveryProofDialog> {
  final ImagePicker _picker = ImagePicker();
  XFile? _selectedImage;
  Uint8List? _imageBytes;
  bool _isUploading = false;

  Future<void> _takePhoto() async {
    try {
      XFile? photo;
      
      // Try camera first
      try {
        photo = await _picker.pickImage(
          source: ImageSource.camera,
          maxWidth: 800,
          maxHeight: 600,
          imageQuality: 70,
        );
      } catch (e) {
        print('Camera not available: $e');
        // Fallback to gallery
        photo = await _picker.pickImage(
          source: ImageSource.gallery,
          maxWidth: 800,
          maxHeight: 600,
          imageQuality: 70,
        );
      }

      if (photo != null) {
        final bytes = await photo.readAsBytes();
        setState(() {
          _selectedImage = photo;
          _imageBytes = bytes;
        });
        print('Image selected: ${photo.path}, size: ${bytes.length} bytes');
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('No image selected'),
            backgroundColor: Colors.orange,
          ),
        );
      }
    } catch (e) {
      print('Error taking photo: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error selecting image: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _uploadPhoto() async {
    if (_selectedImage == null) return;

    setState(() {
      _isUploading = true;
    });

    try {
      await widget.onPhotoTaken(_selectedImage!);
    } catch (e) {
      print('Error in upload callback: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Upload failed: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isUploading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text('Delivery Proof Required'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text('Please take a photo as proof of delivery'),
          SizedBox(height: 20),
          if (_imageBytes != null)
            Container(
              height: 200,
              width: double.infinity,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                image: DecorationImage(
                  image: MemoryImage(_imageBytes!),
                  fit: BoxFit.cover,
                ),
              ),
            )
          else if (_selectedImage != null)
            Container(
              height: 200,
              width: double.infinity,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                color: Colors.grey[200],
              ),
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.image, size: 50, color: Colors.grey),
                    SizedBox(height: 10),
                    Text('Processing image...', style: TextStyle(color: Colors.grey)),
                  ],
                ),
              ),
            )
          else
            Container(
              height: 150,
              width: double.infinity,
              decoration: BoxDecoration(
                color: Colors.grey[200],
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.photo_camera, size: 50, color: Colors.grey),
                  SizedBox(height: 10),
                  Text('No image selected', style: TextStyle(color: Colors.grey)),
                ],
              ),
            ),
          SizedBox(height: 20),
          if (_selectedImage == null)
            Text(
              'Note: On web, use gallery if camera is not available',
              style: TextStyle(fontSize: 12, color: Colors.grey),
              textAlign: TextAlign.center,
            ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: _isUploading ? null : () => Navigator.of(context).pop(),
          child: Text('CANCEL', style: TextStyle(color: Color(0xFF4682B4))),
        ),
        ElevatedButton.icon(
          onPressed: _isUploading ? null : _takePhoto,
          icon: Icon(Icons.camera_alt, color: Colors.white),
          label: Text(_selectedImage == null ? 'TAKE PHOTO' : 'CHANGE PHOTO', style: TextStyle(color: Colors.white)),
          style: ElevatedButton.styleFrom(
            backgroundColor: Color(0xFF87CEEB),
          ),
        ),
        if (_selectedImage != null)
          ElevatedButton.icon(
            onPressed: _isUploading ? null : _uploadPhoto,
            icon: _isUploading 
                ? SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2, valueColor: AlwaysStoppedAnimation(Colors.white)),
                  )
                : Icon(Icons.check, color: Colors.white),
            label: Text(_isUploading ? 'UPLOADING...' : 'CONFIRM DELIVERY', style: TextStyle(color: Colors.white)),
            style: ElevatedButton.styleFrom(
              backgroundColor: Color(0xFF4682B4),
            ),
          ),
      ],
    );
  }
}
class OrderHistoryTab extends StatefulWidget {
  final Map<String, dynamic>? userData;

  const OrderHistoryTab({Key? key, this.userData}) : super(key: key);

  @override
  _OrderHistoryTabState createState() => _OrderHistoryTabState();
}

class _OrderHistoryTabState extends State<OrderHistoryTab> {
  List<dynamic> _orderHistory = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadOrderHistory();
  }

  Future<void> _loadOrderHistory() async {
    if (widget.userData == null) {
      print('No user data available');
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final deliveryPersonId = widget.userData!['delivery_person_id'] ?? widget.userData!['id'];
      
      final response = await http.get(
        Uri.parse('localhostDoordash/get_delivery_history.php?delivery_person_id=$deliveryPersonId'),
        headers: {'Accept': 'application/json'},
      );

      print('History Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _orderHistory = data['orders'] ?? [];
          });
        } else {
          print('History API error: ${data['message']}');
        }
      } else {
        print('History HTTP error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error loading history: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Try refresh for updates'),
          backgroundColor: Colors.orange,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionButton(
        onPressed: _loadOrderHistory,
        child: Icon(Icons.refresh, color: Colors.white),
        backgroundColor: Color(0xFF87CEEB),
        tooltip: 'Refresh History',
      ),
      body: RefreshIndicator(
        onRefresh: _loadOrderHistory,
        child: _isLoading
            ? Center(child: CircularProgressIndicator())
            : _orderHistory.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.history, size: 64, color: Colors.grey),
                        SizedBox(height: 16),
                        Text(
                          'No Delivery History',
                          style: TextStyle(fontSize: 18, color: Colors.grey),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'You haven\'t completed any deliveries yet',
                          style: TextStyle(color: Colors.grey),
                        ),
                        SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: _loadOrderHistory,
                          icon: Icon(Icons.refresh, color: Colors.white),
                          label: Text('Try refresh for updates', style: TextStyle(color: Colors.white)),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Color(0xFF87CEEB),
                          ),
                        ),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: EdgeInsets.all(16),
                    itemCount: _orderHistory.length,
                    itemBuilder: (context, index) {
                      final order = _orderHistory[index];
                      return _OrderCard(
                        order: order,
                        onAccept: null,
                        showAcceptButton: false,
                        showStatusActions: false,
                        hasDeliveryProof: order['delivery_proof_image'] != null,
                      );
                    },
                  ),
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  final dynamic order;
  final VoidCallback? onAccept;
  final bool showAcceptButton;
  final bool showStatusActions;
  final Function(String)? onUpdateStatus;
  final String? nextAction;
  final String? nextStatus;
  final bool hasDeliveryProof;

  const _OrderCard({
    required this.order,
    this.onAccept,
    required this.showAcceptButton,
    this.showStatusActions = false,
    this.onUpdateStatus,
    this.nextAction,
    this.nextStatus,
    this.hasDeliveryProof = false,
  });

  String _getStatusText(String status) {
    return status.replaceAll('_', ' ').toUpperCase();
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'ready_for_pickup':
        return Colors.orange;
      case 'picked_up':
        return Colors.blue;
      case 'on_the_way':
        return Colors.purple;
      case 'delivered':
        return Colors.green;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatAmount(dynamic amount) {
    if (amount == null) return '0.00';
    try {
      double value = double.tryParse(amount.toString()) ?? 0.0;
      return value.toStringAsFixed(2);
    } catch (e) {
      return '0.00';
    }
  }

  String _formatDistance(dynamic distance) {
    if (distance == null) return 'Calculating...';
    try {
      double value = double.tryParse(distance.toString()) ?? 0.0;
      return '${value.toStringAsFixed(1)} miles';
    } catch (e) {
      return 'Calculating...';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.only(bottom: 16),
      elevation: 3,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Order #${order['id']}',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: _getStatusColor(order['status']).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: _getStatusColor(order['status'])),
                  ),
                  child: Text(
                    _getStatusText(order['status']),
                    style: TextStyle(
                      color: _getStatusColor(order['status']),
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
            SizedBox(height: 12),
            _OrderDetailRow(
              icon: Icons.restaurant,
              label: 'Restaurant:',
              value: order['restaurant_name'] ?? 'Unknown',
            ),
            _OrderDetailRow(
              icon: Icons.person,
              label: 'Customer:',
              value: order['customer_name'] ?? 'Unknown',
            ),
            _OrderDetailRow(
              icon: Icons.location_on,
              label: 'Address:',
              value: order['delivery_address'] ?? 'No address',
            ),
            _OrderDetailRow(
              icon: Icons.attach_money,
              label: 'Total:',
              value: '\$${_formatAmount(order['total_amount'])}',
            ),
            _OrderDetailRow(
              icon: Icons.delivery_dining,
              label: 'Delivery Fee:',
              value: '\$${_formatAmount(order['delivery_fee'])}',
            ),
            _OrderDetailRow(
              icon: Icons.directions_car,
              label: 'Distance:',
              value: _formatDistance(order['delivery_distance']),
            ),
            if (hasDeliveryProof) ...[
              SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.verified, size: 16, color: Colors.green),
                  SizedBox(width: 4),
                  Text(
                    'Delivery proof uploaded',
                    style: TextStyle(
                      color: Colors.green,
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ],
            
            if (showAcceptButton && onAccept != null) ...[
              SizedBox(height: 16),
              Container(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: onAccept,
                  icon: Icon(Icons.check, color: Colors.white),
                  label: Text('ACCEPT ORDER', style: TextStyle(color: Colors.white)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Color(0xFF87CEEB),
                    padding: EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              ),
            ],
            
            if (showStatusActions && onUpdateStatus != null && nextAction != null && nextStatus != null) ...[
              SizedBox(height: 16),
              Container(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => onUpdateStatus!(nextStatus!),
                  icon: Icon(Icons.directions_bike, color: Colors.white),
                  label: Text(nextAction!.toUpperCase(), style: TextStyle(color: Colors.white)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Color(0xFF87CEEB),
                    padding: EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _OrderDetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _OrderDetailRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: Color(0xFF4682B4)),
          SizedBox(width: 8),
          Expanded(
            child: RichText(
              text: TextSpan(
                style: TextStyle(color: Colors.black87, fontSize: 14),
                children: [
                  TextSpan(
                    text: '$label ',
                    style: TextStyle(fontWeight: FontWeight.w500),
                  ),
                  TextSpan(text: value),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}