import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (context) => AuthProvider()),
        ChangeNotifierProvider(create: (context) => CartProvider()),
      ],
      child: MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'DoorDash Clone',
      theme: ThemeData(
        primaryColor: Color(0xFF1E90FF),
        colorScheme: ColorScheme.fromSwatch(
          primarySwatch: Colors.blue,
          accentColor: Color(0xFF87CEEB),
        ),
        scaffoldBackgroundColor: Colors.white,
        appBarTheme: AppBarTheme(
          backgroundColor: Color(0xFF1E90FF),
          elevation: 0,
          iconTheme: IconThemeData(color: Colors.white),
          titleTextStyle: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: Color(0xFF1E90FF),
            foregroundColor: Colors.white,
            textStyle: TextStyle(color: Colors.white),
          ),
        ),
        textButtonTheme: TextButtonThemeData(
          style: TextButton.styleFrom(
            foregroundColor: Color(0xFF1E90FF),
          ),
        ),
      ),
      home: MainNavigationPage(),
      debugShowCheckedModeBanner: false,
    );
  }
}

// Auth Provider for Login State
class AuthProvider extends ChangeNotifier {
  bool _isLoggedIn = false;
  String _userName = '';
  int _userId = 0;

  bool get isLoggedIn => _isLoggedIn;
  String get userName => _userName;
  int get userId => _userId;

  void login(String name, int userId) {
    _isLoggedIn = true;
    _userName = name;
    _userId = userId;
    notifyListeners();
  }

  void logout() {
    _isLoggedIn = false;
    _userName = '';
    _userId = 0;
    notifyListeners();
  }
}

// Main Navigation Page with Bottom Navigation
class MainNavigationPage extends StatefulWidget {
  @override
  _MainNavigationPageState createState() => _MainNavigationPageState();
}

class _MainNavigationPageState extends State<MainNavigationPage> {
  int _currentIndex = 0;
  final List<Widget> _pages = [
    RestaurantListPage(),
    OrderHistoryPage(),
    SearchRestaurantsPage(),
  ];

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    
    return Scaffold(
      body: _pages[_currentIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          if (index == 1 && !auth.isLoggedIn) {
            _showLoginPrompt(context);
            return;
          }
          setState(() {
            _currentIndex = index;
          });
        },
        selectedItemColor: Color(0xFF1E90FF),
        unselectedItemColor: Colors.grey,
        items: [
          BottomNavigationBarItem(
            icon: Icon(Icons.restaurant),
            label: 'Restaurants',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.history),
            label: 'Orders',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.search),
            label: 'Search',
          ),
        ],
      ),
    );
  }

  void _showLoginPrompt(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Login Required'),
        content: Text('Please login to view your order history.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Cancel', style: TextStyle(color: Color(0xFF1E90FF))),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _showLoginDialog(context);
            },
            child: Text('Login', style: TextStyle(color: Colors.white)),
            style: ElevatedButton.styleFrom(backgroundColor: Color(0xFF1E90FF)),
          ),
        ],
      ),
    );
  }

  void _showLoginDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => LoginDialog(),
    );
  }
}

// Login Dialog
class LoginDialog extends StatefulWidget {
  @override
  _LoginDialogState createState() => _LoginDialogState();
}

class _LoginDialogState extends State<LoginDialog> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _isLoading = false;

  Future<void> _login() async {
    if (_emailController.text.isEmpty || _passwordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Please enter both email and password'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      // Simulate login - in real app, call your PHP login API
      await Future.delayed(Duration(seconds: 2));
      
      final auth = Provider.of<AuthProvider>(context, listen: false);
      auth.login('Demo User', 11); // Demo user ID 11
      
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Login successful!'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Login failed: $e'),
          backgroundColor: Colors.red,
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
    return AlertDialog(
      title: Text('Login to DoorDash', style: TextStyle(color: Color(0xFF1E90FF))),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          TextField(
            controller: _emailController,
            decoration: InputDecoration(
              labelText: 'Email',
              border: OutlineInputBorder(),
            ),
            keyboardType: TextInputType.emailAddress,
          ),
          SizedBox(height: 12),
          TextField(
            controller: _passwordController,
            decoration: InputDecoration(
              labelText: 'Password',
              border: OutlineInputBorder(),
            ),
            obscureText: true,
          ),
          SizedBox(height: 16),
          Text(
            'For demo purposes, use any email/password',
            style: TextStyle(fontSize: 12, color: Colors.grey),
            textAlign: TextAlign.center,
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text('Cancel', style: TextStyle(color: Color(0xFF1E90FF))),
        ),
        ElevatedButton(
          onPressed: _isLoading ? null : _login,
          child: _isLoading 
              ? SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(color: Colors.white),
                )
              : Text('Login', style: TextStyle(color: Colors.white)),
          style: ElevatedButton.styleFrom(backgroundColor: Color(0xFF1E90FF)),
        ),
      ],
    );
  }
}

// Cart Provider for State Management
class CartProvider extends ChangeNotifier {
  List<CartItem> _items = [];
  Restaurant? _restaurant;

  List<CartItem> get items => _items;
  Restaurant? get restaurant => _restaurant;

  double get subtotal {
    return _items.fold(0, (total, item) => total + (item.price * item.quantity));
  }

  int get totalItems {
    return _items.fold(0, (total, item) => total + item.quantity);
  }

  void addToCart(MenuItem menuItem, Restaurant restaurant, BuildContext context) {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    if (!auth.isLoggedIn) {
      _showLoginPrompt(context);
      return;
    }

    // If adding from different restaurant, clear cart
    if (_restaurant != null && _restaurant!.id != restaurant.id) {
      _items.clear();
    }
    
    _restaurant = restaurant;
    
    final existingIndex = _items.indexWhere((item) => item.id == menuItem.id);
    if (existingIndex >= 0) {
      _items[existingIndex].quantity++;
    } else {
      _items.add(CartItem(
        id: menuItem.id,
        name: menuItem.name,
        price: menuItem.price,
        quantity: 1,
        restaurantId: restaurant.id,
      ));
    }
    notifyListeners();
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('${menuItem.name} added to cart'),
        backgroundColor: Colors.green,
      ),
    );
  }

  void _showLoginPrompt(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Login Required'),
        content: Text('Please login to add items to cart.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('Cancel', style: TextStyle(color: Color(0xFF1E90FF))),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              showDialog(
                context: context,
                builder: (context) => LoginDialog(),
              );
            },
            child: Text('Login', style: TextStyle(color: Colors.white)),
            style: ElevatedButton.styleFrom(backgroundColor: Color(0xFF1E90FF)),
          ),
        ],
      ),
    );
  }

  void removeFromCart(int itemId) {
    _items.removeWhere((item) => item.id == itemId);
    if (_items.isEmpty) {
      _restaurant = null;
    }
    notifyListeners();
  }

  void updateQuantity(int itemId, int quantity, BuildContext context) {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    if (!auth.isLoggedIn) {
      _showLoginPrompt(context);
      return;
    }

    if (quantity <= 0) {
      removeFromCart(itemId);
      return;
    }
    
    final index = _items.indexWhere((item) => item.id == itemId);
    if (index >= 0) {
      _items[index].quantity = quantity;
      notifyListeners();
    }
  }

  void clearCart() {
    _items.clear();
    _restaurant = null;
    notifyListeners();
  }
}

// Search Restaurants Page
class SearchRestaurantsPage extends StatefulWidget {
  @override
  _SearchRestaurantsPageState createState() => _SearchRestaurantsPageState();
}

class _SearchRestaurantsPageState extends State<SearchRestaurantsPage> {
  List<Restaurant> _restaurants = [];
  bool _isLoading = false;
  String _error = '';
  final TextEditingController _searchController = TextEditingController();
  String _currentSearch = '';

  Future<void> _searchRestaurants(String location) async {
    if (location.isEmpty) {
      setState(() {
        _error = 'Please enter a location to search';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _error = '';
      _currentSearch = location;
    });

    try {
      final response = await http.get(
        Uri.parse('localhostDoordash/search_restaurants.php?location=${Uri.encodeComponent(location)}'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _restaurants = (data['restaurants'] as List)
                .map((item) => Restaurant.fromJson(item))
                .toList();
            _isLoading = false;
          });
        } else {
          setState(() {
            _error = data['message'] ?? 'No restaurants found in this location';
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _error = 'Server error: ${response.statusCode}';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Connection error: $e';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    
    return Scaffold(
      appBar: AppBar(
        title: Text('Search Restaurants', style: TextStyle(color: Colors.white)),
        backgroundColor: Color(0xFF1E90FF),
        actions: [
          if (auth.isLoggedIn)
            IconButton(
              icon: Icon(Icons.person, color: Colors.white),
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: Text('Account'),
                    content: Text('Logged in as ${auth.userName}'),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: Text('Close', style: TextStyle(color: Color(0xFF1E90FF))),
                      ),
                      ElevatedButton(
                        onPressed: () {
                          auth.logout();
                          Navigator.pop(context);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text('Logged out successfully'),
                              backgroundColor: Colors.green,
                            ),
                          );
                        },
                        child: Text('Logout', style: TextStyle(color: Colors.white)),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                      ),
                    ],
                  ),
                );
              },
            )
          else
            TextButton(
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => LoginDialog(),
                );
              },
              child: Text('Login', style: TextStyle(color: Colors.white)),
            ),
        ],
      ),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          children: [
            // Search Bar
            Card(
              elevation: 4,
              child: Padding(
                padding: EdgeInsets.all(16),
                child: Column(
                  children: [
                    Text(
                      'Find Restaurants Near You',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _searchController,
                            decoration: InputDecoration(
                              hintText: 'Enter your location (city, address, zip code)',
                              border: OutlineInputBorder(),
                              contentPadding: EdgeInsets.symmetric(horizontal: 12),
                            ),
                            onSubmitted: (value) {
                              _searchRestaurants(value);
                            },
                          ),
                        ),
                        SizedBox(width: 8),
                        IconButton(
                          icon: Icon(Icons.search, color: Colors.white),
                          onPressed: () {
                            _searchRestaurants(_searchController.text);
                          },
                          style: IconButton.styleFrom(
                            backgroundColor: Color(0xFF1E90FF),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            SizedBox(height: 16),

            // Results
            if (_currentSearch.isNotEmpty) ...[
              Text(
                'Results for "$_currentSearch"',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 8),
            ],

            Expanded(
              child: _isLoading
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          CircularProgressIndicator(),
                          SizedBox(height: 16),
                          Text('Searching restaurants...'),
                        ],
                      ),
                    )
                  : _error.isNotEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.location_off, size: 64, color: Colors.grey),
                              SizedBox(height: 16),
                              Text(
                                _error,
                                style: TextStyle(fontSize: 16),
                                textAlign: TextAlign.center,
                              ),
                              SizedBox(height: 16),
                              ElevatedButton(
                                onPressed: () {
                                  _searchRestaurants(_searchController.text);
                                },
                                child: Text('Try Again', style: TextStyle(color: Colors.white)),
                              ),
                            ],
                          ),
                        )
                      : _restaurants.isEmpty
                          ? Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.search, size: 64, color: Colors.grey),
                                  SizedBox(height: 16),
                                  Text(
                                    _currentSearch.isEmpty
                                        ? 'Enter a location to find restaurants'
                                        : 'No restaurants found',
                                    style: TextStyle(fontSize: 16),
                                    textAlign: TextAlign.center,
                                  ),
                                  if (_currentSearch.isEmpty) ...[
                                    SizedBox(height: 8),
                                    Text(
                                      'Try searching for your city, neighborhood, or zip code',
                                      style: TextStyle(fontSize: 14, color: Colors.grey),
                                      textAlign: TextAlign.center,
                                    ),
                                  ],
                                ],
                              ),
                            )
                          : ListView.builder(
                              itemCount: _restaurants.length,
                              itemBuilder: (context, index) {
                                final restaurant = _restaurants[index];
                                return RestaurantCard(
                                  restaurant: restaurant,
                                  onTap: () {
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => RestaurantMenuPage(
                                          restaurant: restaurant,
                                        ),
                                      ),
                                    );
                                  },
                                );
                              },
                            ),
            ),
          ],
        ),
      ),
    );
  }
}

// Restaurant List Page
class RestaurantListPage extends StatefulWidget {
  @override
  _RestaurantListPageState createState() => _RestaurantListPageState();
}

class _RestaurantListPageState extends State<RestaurantListPage> {
  List<Restaurant> _restaurants = [];
  List<Restaurant> _filteredRestaurants = [];
  bool _isLoading = true;
  String _error = '';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchRestaurants();
  }

  Future<void> _fetchRestaurants() async {
    try {
      final response = await http.get(
        Uri.parse('localhostDoordash/get_restaurants.php'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _restaurants = (data['restaurants'] as List)
                .map((item) => Restaurant.fromJson(item))
                .toList();
            _filteredRestaurants = _restaurants;
            _isLoading = false;
          });
        } else {
          setState(() {
            _error = data['message'] ?? 'Failed to load restaurants';
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _error = 'Server error: ${response.statusCode}';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Connection error: $e';
        _isLoading = false;
      });
    }
  }

  void _filterRestaurants(String query) {
    setState(() {
      if (query.isEmpty) {
        _filteredRestaurants = _restaurants;
      } else {
        _filteredRestaurants = _restaurants.where((restaurant) {
          final nameLower = restaurant.name.toLowerCase();
          final cuisineLower = restaurant.cuisineType.toLowerCase();
          final addressLower = restaurant.fullAddress.toLowerCase();
          final queryLower = query.toLowerCase();
          
          return nameLower.contains(queryLower) ||
                 cuisineLower.contains(queryLower) ||
                 addressLower.contains(queryLower);
        }).toList();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final cart = Provider.of<CartProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    
    return Scaffold(
      appBar: AppBar(
        title: Text('Restaurants', style: TextStyle(color: Colors.white)),
        backgroundColor: Color(0xFF1E90FF),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh, color: Colors.white),
            onPressed: _fetchRestaurants,
          ),
          Stack(
            children: [
              IconButton(
                icon: Icon(Icons.shopping_cart, color: Colors.white),
                onPressed: () {
                  if (!auth.isLoggedIn) {
                    showDialog(
                      context: context,
                      builder: (context) => AlertDialog(
                        title: Text('Login Required'),
                        content: Text('Please login to view your cart.'),
                        actions: [
                          TextButton(
                            onPressed: () => Navigator.pop(context),
                            child: Text('Cancel', style: TextStyle(color: Color(0xFF1E90FF))),
                          ),
                          ElevatedButton(
                            onPressed: () {
                              Navigator.pop(context);
                              showDialog(
                                context: context,
                                builder: (context) => LoginDialog(),
                              );
                            },
                            child: Text('Login', style: TextStyle(color: Colors.white)),
                            style: ElevatedButton.styleFrom(backgroundColor: Color(0xFF1E90FF)),
                          ),
                        ],
                      ),
                    );
                    return;
                  }
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => CartPage()),
                  );
                },
              ),
              Positioned(
                right: 8,
                top: 8,
                child: auth.isLoggedIn && cart.totalItems > 0
                    ? CircleAvatar(
                        radius: 8,
                        backgroundColor: Colors.red,
                        child: Text(
                          cart.totalItems.toString(),
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      )
                    : SizedBox(),
              ),
            ],
          ),
          if (auth.isLoggedIn)
            IconButton(
              icon: Icon(Icons.person, color: Colors.white),
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: Text('Account'),
                    content: Text('Logged in as ${auth.userName}'),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: Text('Close', style: TextStyle(color: Color(0xFF1E90FF))),
                      ),
                      ElevatedButton(
                        onPressed: () {
                          auth.logout();
                          Navigator.pop(context);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text('Logged out successfully'),
                              backgroundColor: Colors.green,
                            ),
                          );
                        },
                        child: Text('Logout', style: TextStyle(color: Colors.white)),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                      ),
                    ],
                  ),
                );
              },
            )
          else
            TextButton(
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => LoginDialog(),
                );
              },
              child: Text('Login', style: TextStyle(color: Colors.white)),
            ),
        ],
        bottom: PreferredSize(
          preferredSize: Size.fromHeight(60),
          child: Padding(
            padding: EdgeInsets.all(8),
            child: TextField(
              controller: _searchController,
              onChanged: _filterRestaurants,
              decoration: InputDecoration(
                hintText: 'Search restaurants by name, cuisine, or location...',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                prefixIcon: Icon(Icons.search),
                contentPadding: EdgeInsets.symmetric(horizontal: 12),
              ),
            ),
          ),
        ),
      ),
      body: _isLoading
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(),
                  SizedBox(height: 16),
                  Text('Loading restaurants...'),
                ],
              ),
            )
          : _error.isNotEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error, size: 64, color: Colors.red),
                      SizedBox(height: 16),
                      Text(
                        _error,
                        style: TextStyle(fontSize: 16),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchRestaurants,
                        child: Text('Retry', style: TextStyle(color: Colors.white)),
                      ),
                    ],
                  ),
                )
              : _filteredRestaurants.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.restaurant, size: 64, color: Colors.grey),
                          SizedBox(height: 16),
                          Text(
                            _searchController.text.isEmpty
                                ? 'No restaurants available'
                                : 'No restaurants found for "${_searchController.text}"',
                            style: TextStyle(fontSize: 16),
                          ),
                          if (_searchController.text.isNotEmpty) ...[
                            SizedBox(height: 8),
                            ElevatedButton(
                              onPressed: () {
                                _searchController.clear();
                                _filterRestaurants('');
                              },
                              child: Text('Clear Search', style: TextStyle(color: Colors.white)),
                            ),
                          ],
                        ],
                      ),
                    )
                  : ListView.builder(
                      itemCount: _filteredRestaurants.length,
                      itemBuilder: (context, index) {
                        final restaurant = _filteredRestaurants[index];
                        return RestaurantCard(
                          restaurant: restaurant,
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => RestaurantMenuPage(
                                  restaurant: restaurant,
                                ),
                              ),
                            );
                          },
                        );
                      },
                    ),
    );
  }
}

// Restaurant Card Widget
class RestaurantCard extends StatelessWidget {
  final Restaurant restaurant;
  final VoidCallback onTap;

  const RestaurantCard({
    Key? key,
    required this.restaurant,
    required this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.all(8),
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0xFF1E90FF), Color(0xFF87CEEB)],
                  ),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  Icons.restaurant,
                  color: Colors.white,
                  size: 40,
                ),
              ),
              SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      restaurant.name,
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 4),
                    Text(
                      restaurant.cuisineType,
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 14,
                      ),
                    ),
                    if (restaurant.fullAddress.isNotEmpty) ...[
                      SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.location_on, size: 12, color: Colors.grey),
                          SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              restaurant.fullAddress,
                              style: TextStyle(
                                color: Colors.grey[600],
                                fontSize: 12,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                    SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(Icons.star, color: Colors.amber, size: 16),
                        SizedBox(width: 4),
                        Text(
                          restaurant.rating.toString(),
                          style: TextStyle(fontSize: 14),
                        ),
                        SizedBox(width: 8),
                        Icon(Icons.access_time, color: Colors.grey, size: 16),
                        SizedBox(width: 4),
                        Text(
                          restaurant.deliveryTime,
                          style: TextStyle(fontSize: 14),
                        ),
                      ],
                    ),
                    SizedBox(height: 4),
                    Text(
                      'Delivery: \$${restaurant.deliveryFee}',
                      style: TextStyle(
                        color: Colors.green,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    if (restaurant.description.isNotEmpty) ...[
                      SizedBox(height: 8),
                      Text(
                        restaurant.description,
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 12,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
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

// Restaurant Menu Page
class RestaurantMenuPage extends StatefulWidget {
  final Restaurant restaurant;

  const RestaurantMenuPage({Key? key, required this.restaurant}) : super(key: key);

  @override
  _RestaurantMenuPageState createState() => _RestaurantMenuPageState();
}

class _RestaurantMenuPageState extends State<RestaurantMenuPage> {
  List<MenuItem> _menuItems = [];
  bool _isLoading = true;
  String _error = '';

  @override
  void initState() {
    super.initState();
    _fetchMenuItems();
  }

  Future<void> _fetchMenuItems() async {
    try {
      final response = await http.get(
        Uri.parse('localhostDoordash/get_menu_items.php?restaurant_id=${widget.restaurant.id}'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _menuItems = (data['menu_items'] as List)
                .map((item) => MenuItem.fromJson(item))
                .toList();
            _isLoading = false;
          });
        } else {
          setState(() {
            _error = data['message'] ?? 'Failed to load menu items';
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _error = 'Server error: ${response.statusCode}';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Connection error: $e';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = Provider.of<CartProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.restaurant.name, style: TextStyle(color: Colors.white)),
        backgroundColor: Color(0xFF1E90FF),
        actions: [
          Stack(
            children: [
              IconButton(
                icon: Icon(Icons.shopping_cart, color: Colors.white),
                onPressed: () {
                  if (!auth.isLoggedIn) {
                    showDialog(
                      context: context,
                      builder: (context) => AlertDialog(
                        title: Text('Login Required'),
                        content: Text('Please login to view your cart.'),
                        actions: [
                          TextButton(
                            onPressed: () => Navigator.pop(context),
                            child: Text('Cancel', style: TextStyle(color: Color(0xFF1E90FF))),
                          ),
                          ElevatedButton(
                            onPressed: () {
                              Navigator.pop(context);
                              showDialog(
                                context: context,
                                builder: (context) => LoginDialog(),
                              );
                            },
                            child: Text('Login', style: TextStyle(color: Colors.white)),
                            style: ElevatedButton.styleFrom(backgroundColor: Color(0xFF1E90FF)),
                          ),
                        ],
                      ),
                    );
                    return;
                  }
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => CartPage()),
                  );
                },
              ),
              Positioned(
                right: 8,
                top: 8,
                child: auth.isLoggedIn && cart.totalItems > 0
                    ? CircleAvatar(
                        radius: 8,
                        backgroundColor: Colors.red,
                        child: Text(
                          cart.totalItems.toString(),
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      )
                    : SizedBox(),
              ),
            ],
          ),
        ],
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.grey[50],
              border: Border(bottom: BorderSide(color: Colors.grey[300]!)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.restaurant.cuisineType,
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontSize: 16,
                  ),
                ),
                SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.star, color: Colors.amber, size: 20),
                    SizedBox(width: 4),
                    Text(
                      widget.restaurant.rating.toString(),
                      style: TextStyle(fontSize: 16),
                    ),
                    SizedBox(width: 16),
                    Icon(Icons.access_time, color: Colors.grey, size: 20),
                    SizedBox(width: 4),
                    Text(
                      widget.restaurant.deliveryTime,
                      style: TextStyle(fontSize: 16),
                    ),
                  ],
                ),
                if (widget.restaurant.fullAddress.isNotEmpty) ...[
                  SizedBox(height: 8),
                  Row(
                    children: [
                      Icon(Icons.location_on, size: 16, color: Colors.grey),
                      SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          widget.restaurant.fullAddress,
                          style: TextStyle(
                            color: Colors.grey[600],
                            fontSize: 14,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
          Expanded(
            child: _isLoading
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        CircularProgressIndicator(),
                        SizedBox(height: 16),
                        Text('Loading menu...'),
                      ],
                    ),
                  )
                : _error.isNotEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.error, size: 64, color: Colors.red),
                            SizedBox(height: 16),
                            Text(
                              _error,
                              style: TextStyle(fontSize: 16),
                              textAlign: TextAlign.center,
                            ),
                            SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _fetchMenuItems,
                              child: Text('Retry', style: TextStyle(color: Colors.white)),
                            ),
                          ],
                        ),
                      )
                    : _menuItems.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.fastfood, size: 64, color: Colors.grey),
                                SizedBox(height: 16),
                                Text(
                                  'No menu items available',
                                  style: TextStyle(fontSize: 16),
                                ),
                              ],
                            ),
                          )
                        : ListView.builder(
                            itemCount: _menuItems.length,
                            itemBuilder: (context, index) {
                              final menuItem = _menuItems[index];
                              return MenuItemCard(
                                menuItem: menuItem,
                                restaurant: widget.restaurant,
                              );
                            },
                          ),
          ),
        ],
      ),
    );
  }
}

// Menu Item Card Widget
class MenuItemCard extends StatelessWidget {
  final MenuItem menuItem;
  final Restaurant restaurant;

  const MenuItemCard({Key? key, required this.menuItem, required this.restaurant}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final cart = Provider.of<CartProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    final cartItem = cart.items.firstWhere(
      (item) => item.id == menuItem.id,
      orElse: () => CartItem(id: -1, name: '', price: 0, quantity: 0, restaurantId: -1),
    );
    
    return Card(
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      elevation: 2,
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        menuItem.name,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      if (menuItem.description.isNotEmpty) ...[
                        SizedBox(height: 8),
                        Text(
                          menuItem.description,
                          style: TextStyle(
                            color: Colors.grey[600],
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                auth.isLoggedIn && cartItem.id != -1
                    ? Row(
                        children: [
                          IconButton(
                            icon: Icon(Icons.remove),
                            onPressed: () {
                              cart.updateQuantity(menuItem.id, cartItem.quantity - 1, context);
                            },
                          ),
                          Text(
                            cartItem.quantity.toString(),
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                          IconButton(
                            icon: Icon(Icons.add),
                            onPressed: () {
                              cart.updateQuantity(menuItem.id, cartItem.quantity + 1, context);
                            },
                          ),
                        ],
                      )
                    : ElevatedButton(
                        onPressed: () {
                          cart.addToCart(menuItem, restaurant, context);
                        },
                        child: Text('Add to Cart', style: TextStyle(color: Colors.white)),
                      ),
              ],
            ),
            SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Chip(
                  label: Text(
                    menuItem.category,
                    style: TextStyle(color: Colors.white),
                  ),
                  backgroundColor: Color(0xFF1E90FF),
                ),
                Text(
                  '\$${menuItem.price.toStringAsFixed(2)}',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E90FF),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// Cart Page
class CartPage extends StatefulWidget {
  @override
  _CartPageState createState() => _CartPageState();
}

class _CartPageState extends State<CartPage> {
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _instructionsController = TextEditingController();
  bool _isPlacingOrder = false;

  @override
  Widget build(BuildContext context) {
    final cart = Provider.of<CartProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    
    if (cart.items.isEmpty) {
      return Scaffold(
        appBar: AppBar(
          title: Text('Shopping Cart', style: TextStyle(color: Colors.white)),
          backgroundColor: Color(0xFF1E90FF),
        ),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.shopping_cart_outlined, size: 64, color: Colors.grey),
              SizedBox(height: 16),
              Text(
                'Your cart is empty',
                style: TextStyle(fontSize: 18, color: Colors.grey),
              ),
              SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  Navigator.pop(context);
                },
                child: Text('Browse Restaurants', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        ),
      );
    }

    final taxRate = 0.0888;
    final subtotal = cart.subtotal;
    final taxAmount = subtotal * taxRate;
    final deliveryFee = cart.restaurant?.deliveryFee ?? 2.99;
    final total = subtotal + taxAmount + deliveryFee;

    return Scaffold(
      appBar: AppBar(
        title: Text('Shopping Cart', style: TextStyle(color: Colors.white)),
        backgroundColor: Color(0xFF1E90FF),
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView(
              padding: EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          cart.restaurant?.name ?? 'Restaurant',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'Delivery: \$${deliveryFee.toStringAsFixed(2)}',
                          style: TextStyle(
                            color: Colors.green,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                SizedBox(height: 16),
                Text(
                  'Order Items',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 8),
                ...cart.items.map((item) => CartItemCard(item: item)).toList(),
                SizedBox(height: 16),
                Card(
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Subtotal'),
                            Text('\$${subtotal.toStringAsFixed(2)}'),
                          ],
                        ),
                        SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Tax (8.88%)'),
                            Text('\$${taxAmount.toStringAsFixed(2)}'),
                          ],
                        ),
                        SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Delivery Fee'),
                            Text('\$${deliveryFee.toStringAsFixed(2)}'),
                          ],
                        ),
                        Divider(height: 20),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Total',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              '\$${total.toStringAsFixed(2)}',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF1E90FF),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                SizedBox(height: 16),
                Text(
                  'Delivery Information',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 8),
                TextField(
                  controller: _addressController,
                  decoration: InputDecoration(
                    labelText: 'Delivery Address',
                    hintText: 'Enter your full address',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 2,
                ),
                SizedBox(height: 12),
                TextField(
                  controller: _instructionsController,
                  decoration: InputDecoration(
                    labelText: 'Delivery Instructions (Optional)',
                    hintText: 'Any special instructions for delivery...',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 2,
                ),
              ],
            ),
          ),
          Container(
            padding: EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: Colors.grey[300]!)),
            ),
            child: SizedBox(
              width: double.infinity,
              height: 50,
              child: _isPlacingOrder
                  ? ElevatedButton(
                      onPressed: null,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          ),
                          SizedBox(width: 12),
                          Text('Placing Order...', style: TextStyle(color: Colors.white)),
                        ],
                      ),
                    )
                  : ElevatedButton(
                      onPressed: () => _placeOrder(context, cart, total),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Color(0xFF1E90FF),
                      ),
                      child: Text(
                        'Place Order - \$${total.toStringAsFixed(2)}',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _placeOrder(BuildContext context, CartProvider cart, double total) async {
    if (_addressController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Please enter delivery address'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() {
      _isPlacingOrder = true;
    });

    try {
      // Prepare the order data with correct field names
      final orderData = {
        'customer_id': '11', // Using demo user ID 11
        'restaurant_id': cart.restaurant!.id.toString(),
        'total_amount': total.toStringAsFixed(2),
        'delivery_address': _addressController.text.trim(),
        'instructions': _instructionsController.text.trim(),
        'cart_items': json.encode(cart.items.map((item) => item.toJson()).toList()),
      };

      print('Sending order data: $orderData');

      final response = await http.post(
        Uri.parse('localhostDoordash/placeorderapp.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: orderData,
      );

      print('Response status: ${response.statusCode}');
      print('Response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          cart.clearCart();
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(
              builder: (context) => OrderConfirmationPage(orderId: data['order_id']),
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['message'] ?? 'Failed to place order'),
              backgroundColor: Colors.red,
            ),
          );
        }
      } else {
        throw Exception('Server error: ${response.statusCode} - ${response.body}');
      }
    } catch (e) {
      print('Order placement error: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to place order: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isPlacingOrder = false;
      });
    }
  }
}

// Cart Item Card Widget
class CartItemCard extends StatelessWidget {
  final CartItem item;

  const CartItemCard({Key? key, required this.item}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final cart = Provider.of<CartProvider>(context);
    
    return Card(
      margin: EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: EdgeInsets.all(12),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.name,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    '\$${item.price.toStringAsFixed(2)} each',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ],
              ),
            ),
            Row(
              children: [
                IconButton(
                  icon: Icon(Icons.remove),
                  onPressed: () {
                    cart.updateQuantity(item.id, item.quantity - 1, context);
                  },
                ),
                Text(
                  item.quantity.toString(),
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                IconButton(
                  icon: Icon(Icons.add),
                  onPressed: () {
                    cart.updateQuantity(item.id, item.quantity + 1, context);
                  },
                ),
                SizedBox(width: 8),
                IconButton(
                  icon: Icon(Icons.delete, color: Colors.red),
                  onPressed: () {
                    cart.removeFromCart(item.id);
                  },
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// Order Confirmation Page
class OrderConfirmationPage extends StatelessWidget {
  final int orderId;

  const OrderConfirmationPage({Key? key, required this.orderId}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Order Confirmation', style: TextStyle(color: Colors.white)),
        backgroundColor: Color(0xFF1E90FF),
      ),
      body: Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.check_circle,
                size: 80,
                color: Colors.green,
              ),
              SizedBox(height: 24),
              Text(
                'Order Placed Successfully!',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 16),
              Text(
                'Your order #$orderId has been received and is being prepared.',
                style: TextStyle(fontSize: 16),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 8),
              Text(
                'The restaurant has been notified and will start preparing your food.',
                style: TextStyle(fontSize: 14, color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 32),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  ElevatedButton(
                    onPressed: () {
                      Navigator.pushAndRemoveUntil(
                        context,
                        MaterialPageRoute(builder: (context) => MainNavigationPage()),
                        (route) => false,
                      );
                    },
                    child: Text('Browse More Restaurants', style: TextStyle(color: Colors.white)),
                  ),
                  SizedBox(width: 12),
                  OutlinedButton(
                    onPressed: () {
                      Navigator.pushAndRemoveUntil(
                        context,
                        MaterialPageRoute(builder: (context) => MainNavigationPage()),
                        (route) => route.isFirst,
                      );
                    },
                    child: Text('View My Orders', style: TextStyle(color: Color(0xFF1E90FF))),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// Order History Page
class OrderHistoryPage extends StatefulWidget {
  @override
  _OrderHistoryPageState createState() => _OrderHistoryPageState();
}

class _OrderHistoryPageState extends State<OrderHistoryPage> {
  List<Order> _orders = [];
  bool _isLoading = true;
  String _error = '';

  @override
  void initState() {
    super.initState();
    _fetchOrders();
  }

  Future<void> _fetchOrders() async {
    try {
      final response = await http.get(
        Uri.parse('localhostDoordash/get_user_orders.php?customer_id=11'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _orders = (data['orders'] as List)
                .map((item) => Order.fromJson(item))
                .toList();
            _isLoading = false;
          });
        } else {
          setState(() {
            _error = data['message'] ?? 'Failed to load orders';
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _error = 'Server error: ${response.statusCode}';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Connection error: $e';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    
    if (!auth.isLoggedIn) {
      return Scaffold(
        appBar: AppBar(
          title: Text('My Orders', style: TextStyle(color: Colors.white)),
          backgroundColor: Color(0xFF1E90FF),
        ),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.login, size: 64, color: Colors.grey),
              SizedBox(height: 16),
              Text(
                'Please login to view your order history',
                style: TextStyle(fontSize: 16),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  showDialog(
                    context: context,
                    builder: (context) => LoginDialog(),
                  );
                },
                child: Text('Login', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('My Orders', style: TextStyle(color: Colors.white)),
        backgroundColor: Color(0xFF1E90FF),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh, color: Colors.white),
            onPressed: _fetchOrders,
          ),
        ],
      ),
      body: _isLoading
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(),
                  SizedBox(height: 16),
                  Text('Loading orders...'),
                ],
              ),
            )
          : _error.isNotEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error, size: 64, color: Colors.red),
                      SizedBox(height: 16),
                      Text(
                        _error,
                        style: TextStyle(fontSize: 16),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _fetchOrders,
                        child: Text('Retry', style: TextStyle(color: Colors.white)),
                      ),
                    ],
                  ),
                )
              : _orders.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.receipt_long, size: 64, color: Colors.grey),
                          SizedBox(height: 16),
                          Text(
                            'No orders yet',
                            style: TextStyle(fontSize: 16),
                          ),
                          SizedBox(height: 8),
                          Text(
                            'Your orders will appear here',
                            style: TextStyle(fontSize: 14, color: Colors.grey),
                          ),
                          SizedBox(height: 16),
                          ElevatedButton(
                            onPressed: () {
                              Navigator.pushAndRemoveUntil(
                                context,
                                MaterialPageRoute(builder: (context) => MainNavigationPage()),
                                (route) => false,
                              );
                            },
                            child: Text('Order Food', style: TextStyle(color: Colors.white)),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _fetchOrders,
                      child: ListView.builder(
                        itemCount: _orders.length,
                        itemBuilder: (context, index) {
                          final order = _orders[index];
                          return OrderCard(order: order);
                        },
                      ),
                    ),
    );
  }
}

// Order Card Widget
class OrderCard extends StatelessWidget {
  final Order order;

  const OrderCard({Key? key, required this.order}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.all(8),
      elevation: 4,
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Order #${order.id}',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: _getStatusColor(order.status),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    order.status.replaceAll('_', ' ').toUpperCase(),
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
            SizedBox(height: 8),
            Text(
              order.restaurantName,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Placed on ${_formatDate(order.createdAt)}',
              style: TextStyle(
                color: Colors.grey[600],
                fontSize: 14,
              ),
            ),
            SizedBox(height: 12),
            ...order.items.take(2).map((item) => Padding(
                  padding: EdgeInsets.symmetric(vertical: 2),
                  child: Text(
                    '• ${item.quantity}x ${item.itemName}',
                    style: TextStyle(fontSize: 14),
                  ),
                )),
            if (order.items.length > 2) ...[
              Text(
                '... and ${order.items.length - 2} more items',
                style: TextStyle(
                  color: Colors.grey[600],
                  fontSize: 14,
                ),
              ),
            ],
            SizedBox(height: 12),
            Divider(),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Total',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  '\$${order.totalAmount.toStringAsFixed(2)}',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E90FF),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'delivered':
        return Colors.green;
      case 'pending':
      case 'confirmed':
        return Colors.orange;
      case 'preparing':
        return Colors.blue;
      case 'ready_for_pickup':
      case 'picked_up':
      case 'on_the_way':
        return Colors.purple;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatDate(String dateString) {
    try {
      final date = DateTime.parse(dateString);
      return '${date.day}/${date.month}/${date.year} at ${date.hour}:${date.minute.toString().padLeft(2, '0')}';
    } catch (e) {
      return dateString;
    }
  }
}

// Data Models (Keep all the same as before)
class Restaurant {
  final int id;
  final String name;
  final String cuisineType;
  final double rating;
  final String deliveryTime;
  final double deliveryFee;
  final String description;
  final String address;
  final String city;
  final String state;
  final String zipCode;

  Restaurant({
    required this.id,
    required this.name,
    required this.cuisineType,
    required this.rating,
    required this.deliveryTime,
    required this.deliveryFee,
    required this.description,
    required this.address,
    required this.city,
    required this.state,
    required this.zipCode,
  });

  String get fullAddress {
    List<String> parts = [];
    if (address.isNotEmpty) parts.add(address);
    if (city.isNotEmpty) parts.add(city);
    if (state.isNotEmpty) parts.add(state);
    if (zipCode.isNotEmpty) parts.add(zipCode);
    return parts.join(', ');
  }

  factory Restaurant.fromJson(Map<String, dynamic> json) {
    return Restaurant(
      id: int.tryParse(json['id'].toString()) ?? 0,
      name: json['name']?.toString() ?? '',
      cuisineType: json['cuisine_type']?.toString() ?? '',
      rating: double.tryParse(json['rating'].toString()) ?? 0.0,
      deliveryTime: json['delivery_time']?.toString() ?? '',
      deliveryFee: double.tryParse(json['delivery_fee'].toString()) ?? 0.0,
      description: json['description']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      state: json['state']?.toString() ?? '',
      zipCode: json['zip_code']?.toString() ?? '',
    );
  }
}

class MenuItem {
  final int id;
  final String name;
  final String description;
  final double price;
  final String category;
  final bool isAvailable;

  MenuItem({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
    required this.category,
    required this.isAvailable,
  });

  factory MenuItem.fromJson(Map<String, dynamic> json) {
    return MenuItem(
      id: int.tryParse(json['id'].toString()) ?? 0,
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      price: double.tryParse(json['price'].toString()) ?? 0.0,
      category: json['category']?.toString() ?? '',
      isAvailable: (json['is_available']?.toString() == '1'),
    );
  }
}

class CartItem {
  final int id;
  final String name;
  final double price;
  int quantity;
  final int restaurantId;

  CartItem({
    required this.id,
    required this.name,
    required this.price,
    required this.quantity,
    required this.restaurantId,
  });

  Map<String, dynamic> toJson() {
    return {
      'menu_item_id': id,
      'name': name,
      'price': price,
      'quantity': quantity,
    };
  }
}

class Order {
  final int id;
  final int customerId;
  final int restaurantId;
  final double totalAmount;
  final String deliveryAddress;
  final String status;
  final double deliveryFee;
  final String instructions;
  final String createdAt;
  final String restaurantName;
  final List<OrderItem> items;
  final double subtotal;
  final double taxAmount;
  final double taxRate;

  Order({
    required this.id,
    required this.customerId,
    required this.restaurantId,
    required this.totalAmount,
    required this.deliveryAddress,
    required this.status,
    required this.deliveryFee,
    required this.instructions,
    required this.createdAt,
    required this.restaurantName,
    required this.items,
    required this.subtotal,
    required this.taxAmount,
    required this.taxRate,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: int.tryParse(json['id'].toString()) ?? 0,
      customerId: int.tryParse(json['customer_id'].toString()) ?? 0,
      restaurantId: int.tryParse(json['restaurant_id'].toString()) ?? 0,
      totalAmount: double.tryParse(json['total_amount'].toString()) ?? 0.0,
      deliveryAddress: json['delivery_address']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
      deliveryFee: double.tryParse(json['delivery_fee'].toString()) ?? 0.0,
      instructions: json['instructions']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
      restaurantName: json['restaurant_name']?.toString() ?? '',
      items: (json['items'] as List? ?? [])
          .map((item) => OrderItem.fromJson(item))
          .toList(),
      subtotal: double.tryParse(json['subtotal'].toString()) ?? 0.0,
      taxAmount: double.tryParse(json['tax_amount'].toString()) ?? 0.0,
      taxRate: double.tryParse(json['tax_rate'].toString()) ?? 0.0,
    );
  }
}

class OrderItem {
  final int id;
  final int orderId;
  final int menuItemId;
  final int quantity;
  final double price;
  final String itemName;

  OrderItem({
    required this.id,
    required this.orderId,
    required this.menuItemId,
    required this.quantity,
    required this.price,
    required this.itemName,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      id: int.tryParse(json['id'].toString()) ?? 0,
      orderId: int.tryParse(json['order_id'].toString()) ?? 0,
      menuItemId: int.tryParse(json['menu_item_id'].toString()) ?? 0,
      quantity: int.tryParse(json['quantity'].toString()) ?? 0,
      price: double.tryParse(json['price'].toString()) ?? 0.0,
      itemName: json['item_name']?.toString() ?? 'Unknown Item',
    );
  }
}