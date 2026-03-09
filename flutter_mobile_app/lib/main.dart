// ignore_for_file: curly_braces_in_flow_control_structures, unnecessary_underscores

import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final state = AppState();
  await state.load();
  await Notify.instance.init();
  runApp(App(state: state));
}

class Notify {
  Notify._();
  static final instance = Notify._();
  final _p = FlutterLocalNotificationsPlugin();
  bool _canShow = true;
  Future<void> init() async {
    const init = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(
        requestAlertPermission: false,
        requestBadgePermission: false,
        requestSoundPermission: false,
      ),
      macOS: DarwinInitializationSettings(
        requestAlertPermission: false,
        requestBadgePermission: false,
        requestSoundPermission: false,
      ),
    );
    await _p.initialize(init);
    _canShow = await _requestPermission();
  }

  Future<bool> _requestPermission() async {
    final android = _p.resolvePlatformSpecificImplementation<
      AndroidFlutterLocalNotificationsPlugin
    >();
    if (android != null) {
      final allowed = await android.requestNotificationsPermission();
      if (allowed != null) return allowed;
    }

    final ios = _p.resolvePlatformSpecificImplementation<
      IOSFlutterLocalNotificationsPlugin
    >();
    if (ios != null) {
      final allowed = await ios.requestPermissions(
        alert: true,
        badge: true,
        sound: true,
      );
      if (allowed != null) return allowed;
    }

    final macos = _p.resolvePlatformSpecificImplementation<
      MacOSFlutterLocalNotificationsPlugin
    >();
    if (macos != null) {
      final allowed = await macos.requestPermissions(
        alert: true,
        badge: true,
        sound: true,
      );
      if (allowed != null) return allowed;
    }

    return true;
  }

  Future<void> show(int id, String title, String body) async {
    if (!_canShow) return;
    const details = NotificationDetails(
      android: AndroidNotificationDetails(
        'dd_updates',
        'DD Updates',
        channelDescription: 'Support status updates',
        importance: Importance.high,
        priority: Priority.high,
      ),
    );
    await _p.show(id, title, body, details);
  }
}

class AppState extends ChangeNotifier {
  String apiBase = const String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://localhost:8080/dd4/api',
  );
  String? token;
  Map<String, dynamic>? user;
  List<Map<String, dynamic>> cart = [];
  Set<int> seen = {};

  bool get loggedIn => token != null && token!.isNotEmpty;
  int get cartCount => cart.fold(0, (s, i) => s + _toInt(i['quantity']));
  double get cartTotal => cart.fold(
    0,
    (s, i) => s + (_toDouble(i['price']) * _toInt(i['quantity'])),
  );

  Future<void> load() async {
    final p = await SharedPreferences.getInstance();
    apiBase = _normalizeApiBase(p.getString('api') ?? apiBase);
    token = p.getString('token');
    final u = p.getString('user');
    if (u != null) user = jsonDecode(u) as Map<String, dynamic>;
    final c = p.getString('cart');
    if (c != null) {
      final rows = jsonDecode(c) as List<dynamic>;
      cart = rows
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    }
    seen = (p.getStringList('seen') ?? [])
        .map((e) => int.tryParse(e))
        .whereType<int>()
        .toSet();
  }

  Future<void> setApi(String value) async {
    final n = _normalizeApiBase(value);
    if (n.isEmpty || n == apiBase) return;
    apiBase = n;
    final p = await SharedPreferences.getInstance();
    await p.setString('api', apiBase);
    notifyListeners();
  }

  Future<void> setAuth(String t, Map<String, dynamic> u) async {
    token = t;
    user = u;
    final p = await SharedPreferences.getInstance();
    await p.setString('token', t);
    await p.setString('user', jsonEncode(u));
    notifyListeners();
  }

  Future<void> logout() async {
    token = null;
    user = null;
    final p = await SharedPreferences.getInstance();
    await p.remove('token');
    await p.remove('user');
    notifyListeners();
  }

  Future<void> addToCart(Map<String, dynamic> p) async {
    final id = _toIntOrNull(p['id']);
    if (id == null) return;
    final i = cart.indexWhere((e) => _toIntOrNull(e['id']) == id);
    if (i >= 0) {
      cart[i]['quantity'] = _toInt(cart[i]['quantity']) + 1;
    } else {
      cart.add({
        'id': id,
        'name': '${p['name']}',
        'price': _toDouble(p['price']),
        'quantity': 1,
      });
    }
    await _saveCart();
    notifyListeners();
  }

  Future<void> changeQty(int id, int d) async {
    final i = cart.indexWhere((e) => _toIntOrNull(e['id']) == id);
    if (i < 0) return;
    final n = _toInt(cart[i]['quantity']) + d;
    if (n <= 0) {
      cart.removeAt(i);
    } else {
      cart[i]['quantity'] = n;
    }
    await _saveCart();
    notifyListeners();
  }

  Future<void> clearCart() async {
    cart = [];
    await _saveCart();
    notifyListeners();
  }

  Future<void> rememberSeen(List<int> ids) async {
    seen.addAll(ids);
    final p = await SharedPreferences.getInstance();
    await p.setStringList('seen', seen.map((e) => '$e').toList());
  }

  Future<void> _saveCart() async {
    final p = await SharedPreferences.getInstance();
    await p.setString('cart', jsonEncode(cart));
  }
}

String _normalizeApiBase(String value) {
  var n = value.trim();
  if (n.isEmpty) return n;
  if (!n.startsWith('http://') && !n.startsWith('https://')) {
    n = 'http://$n';
  }
  final uri = Uri.tryParse(n);
  if (uri == null || uri.host.isEmpty) {
    return n.replaceAll(RegExp(r'/$'), '');
  }
  final useLocalDebugPort =
      (uri.host == 'localhost' || uri.host == '127.0.0.1') && !uri.hasPort;
  final rebuilt = Uri(
    scheme: uri.scheme,
    host: _normalizeHost(uri.host),
    port: useLocalDebugPort ? 8080 : (uri.hasPort ? uri.port : null),
    pathSegments: uri.pathSegments.where((s) => s.isNotEmpty),
  );
  return rebuilt.toString().replaceAll(RegExp(r'/$'), '');
}

String _normalizeHost(String host) {
  return host.trim().toLowerCase();
}

final NumberFormat _money = NumberFormat('#,##0.##', 'en_US');
String formatPrice(num? value) =>
    'NGN ${_money.format((value ?? 0).toDouble())}';
double _toDouble(dynamic v) {
  if (v is num) return v.toDouble();
  if (v is String) return double.tryParse(v.trim()) ?? 0;
  return 0;
}

int _toInt(dynamic v) {
  if (v is int) return v;
  if (v is num) return v.toInt();
  if (v is String) return int.tryParse(v.trim()) ?? 0;
  return 0;
}

int? _toIntOrNull(dynamic v) {
  if (v == null) return null;
  if (v is int) return v;
  if (v is num) return v.toInt();
  if (v is String) return int.tryParse(v.trim());
  return null;
}

class Api {
  Api(this.base, {this.token});
  final String base;
  final String? token;
  Uri u(String path, [Map<String, String>? q]) =>
      Uri.parse('$base/$path').replace(queryParameters: q);
  Map<String, String> h([bool json = false]) => {
    if (json) 'Content-Type': 'application/json',
    if (token != null && token!.isNotEmpty) 'Authorization': 'Bearer $token',
  };

  Future<(List<Map<String, dynamic>>, List<Map<String, dynamic>>)> products({
    String? category,
    double? maxPrice,
  }) async {
    final q = <String, String>{'action': 'get_all', 'limit': '100'};
    if (category != null && category.isNotEmpty) q['category'] = category;
    if (maxPrice != null && maxPrice > 0) q['max_price'] = '$maxPrice';
    final r = await http.get(u('products.php', q), headers: h());
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('Products failed');
    final products = (d['products'] as List)
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
    final categories = ((d['categories'] ?? []) as List)
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
    return (products, categories);
  }

  Future<List<Map<String, dynamic>>> featuredProducts({int limit = 6}) async {
    final r = await http.get(
      u('products.php', {'action': 'get_featured', 'limit': '$limit'}),
      headers: h(),
    );
    if (r.statusCode >= 400) throw Exception('Featured products failed');
    final d = jsonDecode(r.body);
    if (d is List) {
      return d
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    }
    if (d is Map) {
      final rows = (d['products'] ?? d['items'] ?? []) as List;
      return rows
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    }
    return [];
  }

  Future<List<Map<String, dynamic>>> galleryItems({int limit = 8}) async {
    final r = await http.get(
      u('gallery.php', {'action': 'get_all'}),
      headers: h(),
    );
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('Gallery failed');
    final rows = ((d['items'] ?? []) as List)
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
    return rows.take(limit).toList();
  }

  Future<Map<String, dynamic>> product(int id) async {
    final r = await http.get(
      u('products.php', {'action': 'get_one', 'id': '$id'}),
      headers: h(),
    );
    final d = jsonDecode(r.body) as Map<String, dynamic>;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('Product failed');
    return d;
  }

  Future<Map<String, dynamic>> login(String user, String pass) async {
    final r = await http.post(
      u('auth.php', {'action': 'login'}),
      headers: h(true),
      body: jsonEncode({'username_or_email': user, 'password': pass}),
    );
    final d = jsonDecode(r.body) as Map<String, dynamic>;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('${d['message'] ?? 'Login failed'}');
    return d;
  }

  Future<Map<String, dynamic>> register(
    String username,
    String email,
    String pass,
  ) async {
    final r = await http.post(
      u('auth.php', {'action': 'register'}),
      headers: h(true),
      body: jsonEncode({
        'username': username,
        'email': email,
        'password': pass,
      }),
    );
    final d = jsonDecode(r.body) as Map<String, dynamic>;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('${d['message'] ?? 'Register failed'}');
    return d;
  }

  Future<String> support(Map<String, String> payload) async {
    final r = await http.post(
      u('support.php'),
      headers: h(true),
      body: jsonEncode(payload),
    );
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      return '${d['message'] ?? 'Support failed'}';
    return 'Support ticket #${d['request_id'] ?? d['ticket_id']} created';
  }

  Future<String> install(Map<String, String> payload) async {
    final r = await http.post(
      u('solar_requests.php'),
      headers: h(true),
      body: jsonEncode(payload),
    );
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      return '${d['message'] ?? 'Request failed'}';
    return 'Installation request #${d['request_id']} created';
  }

  Future<List<Map<String, dynamic>>> supportList() async {
    final r = await http.get(
      u('support.php', {'action': 'list', 'type': 'support'}),
      headers: h(),
    );
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('${d['message'] ?? 'Support list failed'}');
    return ((d['requests'] ?? []) as List)
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<List<Map<String, dynamic>>> installList() async {
    final r = await http.get(
      u('solar_requests.php', {'action': 'list'}),
      headers: h(),
    );
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('${d['message'] ?? 'Install list failed'}');
    return ((d['requests'] ?? []) as List)
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<List<Map<String, dynamic>>> notifications() async {
    final r = await http.get(
      u('support.php', {'action': 'notifications', 'unread_only': '1'}),
      headers: h(),
    );
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('Notifications failed');
    return ((d['notifications'] ?? []) as List)
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<List<Map<String, dynamic>>> requestReplies(int requestId) async {
    final r = await http.get(
      u('support.php', {'action': 'replies', 'request_id': '$requestId'}),
      headers: h(),
    );
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('${d['message'] ?? 'Replies failed'}');
    return ((d['replies'] ?? []) as List)
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<String> replyUpdate(int requestId, String message) async {
    final r = await http.post(
      u('support.php', {'action': 'reply'}),
      headers: h(true),
      body: jsonEncode({'request_id': requestId, 'message': message}),
    );
    final d = jsonDecode(r.body) as Map;
    if (r.statusCode >= 400 || d['status'] != 'success')
      return '${d['message'] ?? 'Reply failed'}';
    return '${d['message'] ?? 'Reply sent'}';
  }

  Future<Map<String, dynamic>> order(
    List<Map<String, dynamic>> cart,
    Map<String, dynamic> user,
    String phone,
    String address,
  ) async {
    final r = await http.post(
      u('orders.php', {'action': 'create'}),
      headers: h(true),
      body: jsonEncode({
        'cart_items': cart,
        'customer_email': '${user['email'] ?? ''}',
        'customer_name': '${user['username'] ?? ''}',
        'customer_phone': phone,
        'customer_address': address,
      }),
    );
    final d = jsonDecode(r.body) as Map<String, dynamic>;
    if (r.statusCode >= 400 || d['status'] != 'success')
      throw Exception('${d['message'] ?? 'Order failed'}');
    return d;
  }
}

class App extends StatelessWidget {
  const App({super.key, required this.state});
  final AppState state;
  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: state,
      builder: (_, __) => MaterialApp(
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: const ColorScheme.light(
            primary: Color(0xFF0057B8),
            onPrimary: Colors.white,
            secondary: Color(0xFFFFC107),
            onSecondary: Color(0xFF1A1A1A),
            surface: Colors.white,
            onSurface: Color(0xFF1A1A1A),
          ),
          scaffoldBackgroundColor: Colors.white,
          appBarTheme: const AppBarTheme(
            backgroundColor: Color(0xFF0057B8),
            foregroundColor: Colors.white,
          ),
          navigationBarTheme: const NavigationBarThemeData(
            backgroundColor: Colors.white,
            indicatorColor: Color(0xFFFFE082),
          ),
          useMaterial3: true,
        ),
        home: Home(state: state),
      ),
    );
  }
}

class Home extends StatefulWidget {
  const Home({super.key, required this.state});
  final AppState state;
  @override
  State<Home> createState() => _HomeState();
}

class _HomeState extends State<Home> {
  int i = 0;
  Timer? t;

  @override
  void initState() {
    super.initState();
    widget.state.addListener(_onState);
    _onState();
  }

  @override
  void dispose() {
    widget.state.removeListener(_onState);
    t?.cancel();
    super.dispose();
  }

  void _onState() {
    if (widget.state.loggedIn && t == null) {
      t = Timer.periodic(const Duration(seconds: 45), (_) => _poll());
      _poll();
    } else if (!widget.state.loggedIn) {
      t?.cancel();
      t = null;
    }
  }

  Future<void> _poll() async {
    if (!widget.state.loggedIn) return;
    final api = Api(widget.state.apiBase, token: widget.state.token);
    try {
      final rows = await api.notifications();
      final fresh = <int>[];
      for (final n in rows) {
        final id = _toIntOrNull(n['id']);
        if (id == null || widget.state.seen.contains(id)) continue;
        fresh.add(id);
        await Notify.instance.show(
          id,
          '${n['title'] ?? 'DD Update'}',
          '${n['body'] ?? ''}',
        );
      }
      if (fresh.isNotEmpty) await widget.state.rememberSeen(fresh);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeTab(
        state: widget.state,
        onShopTap: () => setState(() => i = 1),
        onInstallTap: () => setState(() => i = 4),
      ),
      Products(state: widget.state),
      Cart(state: widget.state),
      Support(state: widget.state),
      Install(state: widget.state),
    ];
    return Scaffold(
      appBar: AppBar(title: const Text('DDbuildingTech Mobile')),
      body: SafeArea(child: pages[i]),
      bottomNavigationBar: NavigationBar(
        selectedIndex: i,
        onDestinationSelected: (v) => setState(() => i = v),
        destinations: [
          const NavigationDestination(icon: Icon(Icons.home), label: 'Home'),
          const NavigationDestination(
            icon: Icon(Icons.storefront),
            label: 'Shop',
          ),
          NavigationDestination(
            icon: Badge(
              isLabelVisible: widget.state.cartCount > 0,
              label: Text('${widget.state.cartCount}'),
              child: const Icon(Icons.shopping_cart),
            ),
            label: 'Cart',
          ),
          const NavigationDestination(
            icon: Icon(Icons.support_agent),
            label: 'Support',
          ),
          const NavigationDestination(
            icon: Icon(Icons.solar_power),
            label: 'Install',
          ),
        ],
      ),
    );
  }
}

class HomeTab extends StatefulWidget {
  const HomeTab({
    super.key,
    required this.state,
    required this.onShopTap,
    required this.onInstallTap,
  });
  final AppState state;
  final VoidCallback onShopTap;
  final VoidCallback onInstallTap;

  @override
  State<HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<HomeTab> {
  late Future<(List<Map<String, dynamic>>, List<Map<String, dynamic>>)> f;

  Future<(List<Map<String, dynamic>>, List<Map<String, dynamic>>)>
  _load() async {
    final api = Api(widget.state.apiBase, token: widget.state.token);
    final featured = await api.featuredProducts(limit: 6);
    final gallery = await api.galleryItems(limit: 8);
    return (featured, gallery);
  }

  @override
  void initState() {
    super.initState();
    f = _load();
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async => setState(() => f = _load()),
      child: FutureBuilder<(List<Map<String, dynamic>>, List<Map<String, dynamic>>)>(
        future: f,
        builder: (_, s) {
          final featured = s.data?.$1 ?? <Map<String, dynamic>>[];
          final gallery = s.data?.$2 ?? <Map<String, dynamic>>[];
          return ListView(
            padding: const EdgeInsets.all(12),
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(14),
                  gradient: const LinearGradient(
                    colors: [Color(0xFF0057B8), Color(0xFF0D6FD2)],
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Powering Smart, Secure Buildings',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Shop trusted products, request installations, and track support in one app.',
                      style: TextStyle(color: Colors.white),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        FilledButton(
                          style: FilledButton.styleFrom(
                            backgroundColor: const Color(0xFFFFC107),
                            foregroundColor: const Color(0xFF1A1A1A),
                          ),
                          onPressed: widget.onShopTap,
                          child: const Text('Shop Now'),
                        ),
                        const SizedBox(width: 8),
                        OutlinedButton(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.white,
                            side: const BorderSide(color: Colors.white),
                          ),
                          onPressed: widget.onInstallTap,
                          child: const Text('Request Installation'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: const [
                  Chip(label: Text('Solar Solutions')),
                  Chip(label: Text('CCTV')),
                  Chip(label: Text('Fire Alarm')),
                  Chip(label: Text('Building Automation')),
                ],
              ),
              const SizedBox(height: 14),
              const Text(
                'Featured Products',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              if (s.connectionState != ConnectionState.done)
                const Padding(
                  padding: EdgeInsets.all(16),
                  child: Center(child: CircularProgressIndicator()),
                )
              else if (featured.isEmpty)
                const Card(
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: Text('No featured products yet'),
                  ),
                )
              else
                GridView.builder(
                  itemCount: featured.length,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                    childAspectRatio: 0.65,
                  ),
                  itemBuilder: (_, k) {
                    final p = featured[k];
                    return Card(
                      clipBehavior: Clip.antiAlias,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: SizedBox(
                              width: double.infinity,
                              child: Image.network(
                                '${p['image_main_url'] ?? ''}',
                                fit: BoxFit.cover,
                                errorBuilder: (_, __, ___) => Container(
                                  color: const Color(0xFFEFF4FA),
                                  alignment: Alignment.center,
                                  child: const Icon(Icons.image_not_supported),
                                ),
                              ),
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.all(8),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  '${p['name']}',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  formatPrice(_toDouble(p['price'])),
                                  style: const TextStyle(
                                    color: Color(0xFF0057B8),
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              const SizedBox(height: 14),
              const Text(
                'From Our Website Gallery',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              SizedBox(
                height: 170,
                child: gallery.isEmpty
                    ? const Center(child: Text('No gallery items yet'))
                    : ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: gallery.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 10),
                        itemBuilder: (_, k) {
                          final g = gallery[k];
                          return SizedBox(
                            width: 220,
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(12),
                              child: Stack(
                                fit: StackFit.expand,
                                children: [
                                  Image.network(
                                    '${g['primary_image_url'] ?? ''}',
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) => Container(
                                      color: const Color(0xFFEFF4FA),
                                      alignment: Alignment.center,
                                      child: const Icon(
                                        Icons.image_not_supported,
                                      ),
                                    ),
                                  ),
                                  Container(
                                    alignment: Alignment.bottomLeft,
                                    padding: const EdgeInsets.all(8),
                                    decoration: const BoxDecoration(
                                      gradient: LinearGradient(
                                        begin: Alignment.topCenter,
                                        end: Alignment.bottomCenter,
                                        colors: [
                                          Colors.transparent,
                                          Color(0xAA000000),
                                        ],
                                      ),
                                    ),
                                    child: Text(
                                      '${g['title'] ?? 'Project'}',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
              ),
              const SizedBox(height: 12),
              Text(
                widget.state.loggedIn
                    ? 'Logged in as ${widget.state.user?['username'] ?? widget.state.user?['email']}'
                    : 'Not logged in',
              ),
              const SizedBox(height: 8),
              if (!widget.state.loggedIn)
                FilledButton(
                  onPressed: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => Auth(state: widget.state),
                    ),
                  ),
                  child: const Text('Login / Register'),
                ),
              if (widget.state.loggedIn) ...[
                FilledButton(
                  onPressed: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => MyRequests(state: widget.state),
                    ),
                  ),
                  child: const Text('My Requests'),
                ),
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: widget.state.logout,
                  child: const Text('Logout'),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}

class Products extends StatefulWidget {
  const Products({super.key, required this.state});
  final AppState state;
  @override
  State<Products> createState() => _ProductsState();
}

class _ProductsState extends State<Products> {
  late Future<(List<Map<String, dynamic>>, List<Map<String, dynamic>>)> f;
  final maxPriceCtrl = TextEditingController();
  String selectedCategory = '';

  Future<(List<Map<String, dynamic>>, List<Map<String, dynamic>>)> _load() {
    return Api(widget.state.apiBase, token: widget.state.token).products(
      category: selectedCategory.isEmpty ? null : selectedCategory,
      maxPrice: double.tryParse(maxPriceCtrl.text.trim()),
    );
  }

  @override
  void initState() {
    super.initState();
    f = _load();
  }

  @override
  void dispose() {
    maxPriceCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async => setState(() => f = _load()),
      child:
          FutureBuilder<
            (List<Map<String, dynamic>>, List<Map<String, dynamic>>)
          >(
            future: f,
            builder: (_, s) {
              if (s.connectionState != ConnectionState.done)
                return const Center(child: CircularProgressIndicator());
              if (s.hasError)
                return ListView(
                  children: [
                    const SizedBox(height: 100),
                    Center(child: Text('${s.error}')),
                  ],
                );
              final rows = s.data?.$1 ?? [];
              final categories = s.data?.$2 ?? [];
              return ListView(
                padding: const EdgeInsets.all(12),
                children: [
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        children: [
                          DropdownButtonFormField<String>(
                            initialValue: selectedCategory.isEmpty
                                ? null
                                : selectedCategory,
                            decoration: const InputDecoration(
                              labelText: 'Category',
                              border: OutlineInputBorder(),
                            ),
                            items: [
                              const DropdownMenuItem<String>(
                                value: '',
                                child: Text('All categories'),
                              ),
                              ...categories.map(
                                (c) => DropdownMenuItem<String>(
                                  value: '${c['id']}',
                                  child: Text('${c['name']}'),
                                ),
                              ),
                            ],
                            onChanged: (v) => setState(() {
                              selectedCategory = v ?? '';
                              f = _load();
                            }),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: maxPriceCtrl,
                                  keyboardType: TextInputType.number,
                                  decoration: const InputDecoration(
                                    labelText: 'Max price',
                                    border: OutlineInputBorder(),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              FilledButton(
                                onPressed: () => setState(() => f = _load()),
                                child: const Text('Apply'),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Text(
                      '${rows.length} products',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                  GridView.builder(
                    itemCount: rows.length,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    gridDelegate:
                        const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 10,
                          mainAxisSpacing: 10,
                          childAspectRatio: 0.62,
                        ),
                    itemBuilder: (_, k) {
                      final p = rows[k];
                      return Card(
                        clipBehavior: Clip.antiAlias,
                        child: InkWell(
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => ProductDetails(
                                state: widget.state,
                                id: _toInt(p['id']),
                              ),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                child: SizedBox(
                                  width: double.infinity,
                                  child: Image.network(
                                    '${p['image_main_url'] ?? ''}',
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) => Container(
                                      color: const Color(0xFFEFF4FA),
                                      alignment: Alignment.center,
                                      child: const Icon(
                                        Icons.image_not_supported,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                              Padding(
                                padding: const EdgeInsets.fromLTRB(
                                  10,
                                  8,
                                  10,
                                  8,
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      '${p['name']}',
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    Text(
                                      formatPrice(_toDouble(p['price'])),
                                      style: const TextStyle(
                                        color: Color(0xFF0057B8),
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    const SizedBox(height: 8),
                                    SizedBox(
                                      width: double.infinity,
                                      height: 34,
                                      child: FilledButton(
                                        onPressed: () async =>
                                            widget.state.addToCart(p),
                                        child: const Text('Add to Cart'),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ],
              );
            },
          ),
    );
  }
}

class ProductDetails extends StatelessWidget {
  const ProductDetails({super.key, required this.state, required this.id});
  final AppState state;
  final int id;
  @override
  Widget build(BuildContext context) {
    final f = Api(state.apiBase, token: state.token).product(id);
    return Scaffold(
      appBar: AppBar(title: const Text('Product Details')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: f,
        builder: (_, s) {
          if (s.connectionState != ConnectionState.done)
            return const Center(child: CircularProgressIndicator());
          if (s.hasError) return Center(child: Text('${s.error}'));
          final p = Map<String, dynamic>.from(
            (s.data?['product'] as Map?) ?? {},
          );
          return ListView(
            padding: const EdgeInsets.all(12),
            children: [
              if ((p['image_main_url'] ?? '').toString().isNotEmpty)
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Image.network(
                    '${p['image_main_url']}',
                    height: 220,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      height: 220,
                      color: const Color(0xFFEFF4FA),
                      alignment: Alignment.center,
                      child: const Icon(Icons.image_not_supported),
                    ),
                  ),
                ),
              const SizedBox(height: 12),
              Text(
                '${p['name']}',
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(formatPrice(_toDouble(p['price']))),
              const SizedBox(height: 10),
              Text('${p['description'] ?? ''}'),
              const SizedBox(height: 12),
              FilledButton.icon(
                onPressed: () async {
                  await state.addToCart(p);
                  if (!context.mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Added to cart')),
                  );
                },
                icon: const Icon(Icons.add_shopping_cart),
                label: const Text('Add to Cart'),
              ),
            ],
          );
        },
      ),
    );
  }
}

class Cart extends StatefulWidget {
  const Cart({super.key, required this.state});
  final AppState state;
  @override
  State<Cart> createState() => _CartState();
}

class _CartState extends State<Cart> {
  final phone = TextEditingController();
  final address = TextEditingController();
  bool loading = false;
  @override
  void dispose() {
    phone.dispose();
    address.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.state.cart.isEmpty)
      return const Center(child: Text('Cart is empty'));
    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        ...widget.state.cart.map(
          (i) => ListTile(
            title: Text('${i['name']}'),
            subtitle: Text(
              '${formatPrice(_toDouble(i['price']))} x ${_toInt(i['quantity'])}',
            ),
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                IconButton(
                  onPressed: () => widget.state.changeQty(_toInt(i['id']), -1),
                  icon: const Icon(Icons.remove_circle_outline),
                ),
                IconButton(
                  onPressed: () => widget.state.changeQty(_toInt(i['id']), 1),
                  icon: const Icon(Icons.add_circle_outline),
                ),
              ],
            ),
          ),
        ),
        Text('Total: ${formatPrice(widget.state.cartTotal)}'),
        const SizedBox(height: 8),
        TextField(
          controller: phone,
          decoration: const InputDecoration(
            labelText: 'Phone',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 8),
        TextField(
          controller: address,
          decoration: const InputDecoration(
            labelText: 'Address',
            border: OutlineInputBorder(),
          ),
          maxLines: 2,
        ),
        const SizedBox(height: 8),
        FilledButton(
          onPressed: loading
              ? null
              : () async {
                  if (!widget.state.loggedIn) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Login required')),
                    );
                    return;
                  }
                  setState(() => loading = true);
                  try {
                    final d =
                        await Api(
                          widget.state.apiBase,
                          token: widget.state.token,
                        ).order(
                          widget.state.cart,
                          widget.state.user ?? {},
                          phone.text.trim(),
                          address.text.trim(),
                        );
                    await widget.state.clearCart();
                    if (!context.mounted) return;
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text('Order ${d['reference']} created'),
                      ),
                    );
                  } catch (e) {
                    if (!context.mounted) return;
                    ScaffoldMessenger.of(
                      context,
                    ).showSnackBar(SnackBar(content: Text('$e')));
                  } finally {
                    if (mounted) setState(() => loading = false);
                  }
                },
          child: Text(loading ? 'Placing order...' : 'Place Order'),
        ),
      ],
    );
  }
}

class Support extends StatelessWidget {
  const Support({super.key, required this.state});
  final AppState state;
  @override
  Widget build(BuildContext context) {
    if (!state.loggedIn) {
      return _AuthRequiredView(
        title: 'Support',
        message: 'Login or register to submit support tickets.',
        state: state,
      );
    }
    return _FormView(
      title: 'Support',
      fields: const ['name', 'email', 'phone', 'subject', 'message'],
      submitText: 'Submit Support Ticket',
      requestsTitle: 'Your Support Tickets',
      loadRequests: () => Api(state.apiBase, token: state.token).supportList(),
      onSubmit: (m) => Api(state.apiBase, token: state.token).support({
        'contact_name': m['name']!,
        'contact_email': m['email']!,
        'contact_phone': m['phone']!,
        'subject': m['subject']!,
        'message': m['message']!,
        'channel': 'mobile_app',
      }),
      onReply: (id, message) =>
          Api(state.apiBase, token: state.token).replyUpdate(id, message),
      loadReplies: (id) =>
          Api(state.apiBase, token: state.token).requestReplies(id),
    );
  }
}

class Install extends StatelessWidget {
  const Install({super.key, required this.state});
  final AppState state;
  @override
  Widget build(BuildContext context) {
    if (!state.loggedIn) {
      return _AuthRequiredView(
        title: 'Installation',
        message: 'Login or register to submit installation requests.',
        state: state,
      );
    }
    return _FormView(
      title: 'Installation',
      fields: const [
        'name',
        'email',
        'phone',
        'location',
        'system_size',
        'preferred_visit_date',
        'notes',
      ],
      submitText: 'Submit Installation Request',
      requestsTitle: 'Your Installation Requests',
      loadRequests: () => Api(state.apiBase, token: state.token).installList(),
      onSubmit: (m) => Api(state.apiBase, token: state.token).install({
        'contact_name': m['name']!,
        'contact_email': m['email']!,
        'contact_phone': m['phone']!,
        'location': m['location']!,
        'system_size': m['system_size']!,
        'preferred_visit_date': m['preferred_visit_date']!,
        'notes': m['notes']!,
        'channel': 'mobile_app',
      }),
      onReply: (id, message) =>
          Api(state.apiBase, token: state.token).replyUpdate(id, message),
      loadReplies: (id) =>
          Api(state.apiBase, token: state.token).requestReplies(id),
    );
  }
}

class _AuthRequiredView extends StatelessWidget {
  const _AuthRequiredView({
    required this.title,
    required this.message,
    required this.state,
  });
  final String title;
  final String message;
  final AppState state;

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Container(
          constraints: const BoxConstraints(maxWidth: 460),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            boxShadow: const [
              BoxShadow(
                blurRadius: 24,
                offset: Offset(0, 10),
                color: Color(0x22000000),
              ),
            ],
            gradient: const LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Color(0xFFFFFFFF), Color(0xFFF6FAFF)],
            ),
            border: Border.all(color: const Color(0xFFE2ECF9)),
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: const Color(0xFFE9F1FC),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(
                        Icons.lock_person_outlined,
                        color: Color(0xFF0057B8),
                      ),
                    ),
                    const SizedBox(width: 10),
                    const Text(
                      'Secure Access',
                      style: TextStyle(
                        fontSize: 13,
                        color: Color(0xFF0057B8),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  '$title Requires Login',
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  message,
                  style: const TextStyle(height: 1.4, color: Color(0xFF435266)),
                ),
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF8E1),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFFFE082)),
                  ),
                  child: const Row(
                    children: [
                      Icon(
                        Icons.info_outline,
                        size: 18,
                        color: Color(0xFF8C6D00),
                      ),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'After login, your submitted requests can be tracked in My Requests.',
                          style: TextStyle(fontSize: 12.5),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        style: FilledButton.styleFrom(
                          backgroundColor: cs.primary,
                          foregroundColor: cs.onPrimary,
                          minimumSize: const Size(0, 46),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        onPressed: () => Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => Auth(state: state)),
                        ),
                        icon: const Icon(Icons.login),
                        label: const Text('Login / Register'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size(46, 46),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      onPressed: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(
                              'Open Home/Shop to continue browsing.',
                            ),
                          ),
                        );
                      },
                      child: const Icon(Icons.home_outlined),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class Auth extends StatefulWidget {
  const Auth({super.key, required this.state});
  final AppState state;
  @override
  State<Auth> createState() => _AuthState();
}

class _AuthState extends State<Auth> {
  final u = TextEditingController();
  final e = TextEditingController();
  final p = TextEditingController();
  bool login = true, loading = false;
  String err = '';
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF0057B8), Color(0xFF0D6FD2), Color(0xFFF3F7FC)],
            stops: [0.0, 0.26, 0.26],
          ),
        ),
        child: SafeArea(
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.arrow_back, color: Colors.white),
                  ),
                  const SizedBox(width: 4),
                  const Text(
                    'Ddbuildingtech',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              Text(
                login ? 'Welcome Back' : 'Create Account',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 30,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                login
                    ? 'Login to submit and track your requests.'
                    : 'Register to access support and installation requests.',
                style: const TextStyle(color: Color(0xFFEAF1FB), fontSize: 14),
              ),
              const SizedBox(height: 18),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                  boxShadow: const [
                    BoxShadow(
                      blurRadius: 24,
                      offset: Offset(0, 8),
                      color: Color(0x22000000),
                    ),
                  ],
                ),
                child: Column(
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        color: const Color(0xFFF2F6FB),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: GestureDetector(
                              onTap: () => setState(() => login = true),
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 10,
                                ),
                                decoration: BoxDecoration(
                                  color: login
                                      ? const Color(0xFF0057B8)
                                      : Colors.transparent,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                alignment: Alignment.center,
                                child: Text(
                                  'Login',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    color: login
                                        ? Colors.white
                                        : const Color(0xFF4B5D74),
                                  ),
                                ),
                              ),
                            ),
                          ),
                          Expanded(
                            child: GestureDetector(
                              onTap: () => setState(() => login = false),
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 10,
                                ),
                                decoration: BoxDecoration(
                                  color: !login
                                      ? const Color(0xFF0057B8)
                                      : Colors.transparent,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                alignment: Alignment.center,
                                child: Text(
                                  'Register',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    color: !login
                                        ? Colors.white
                                        : const Color(0xFF4B5D74),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: u,
                      decoration: InputDecoration(
                        labelText: login ? 'Username or Email' : 'Username',
                        filled: true,
                        fillColor: const Color(0xFFF8FAFD),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                    ),
                    if (!login) ...[
                      const SizedBox(height: 10),
                      TextField(
                        controller: e,
                        decoration: InputDecoration(
                          labelText: 'Email',
                          filled: true,
                          fillColor: const Color(0xFFF8FAFD),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 10),
                    TextField(
                      controller: p,
                      decoration: InputDecoration(
                        labelText: 'Password',
                        filled: true,
                        fillColor: const Color(0xFFF8FAFD),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      obscureText: true,
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      height: 46,
                      child: FilledButton(
                        onPressed: loading
                            ? null
                            : () async {
                                setState(() {
                                  loading = true;
                                  err = '';
                                });
                                try {
                                  final api = Api(widget.state.apiBase);
                                  final d = login
                                      ? await api.login(
                                          u.text.trim(),
                                          p.text.trim(),
                                        )
                                      : await api.register(
                                          u.text.trim(),
                                          e.text.trim(),
                                          p.text.trim(),
                                        );
                                  await widget.state.setAuth(
                                    '${d['token']}',
                                    Map<String, dynamic>.from(d['user'] as Map),
                                  );
                                  if (!context.mounted) return;
                                  Navigator.pop(context);
                                } catch (x) {
                                  setState(() => err = '$x');
                                } finally {
                                  if (mounted) setState(() => loading = false);
                                }
                              },
                        child: Text(
                          loading
                              ? 'Please wait...'
                              : (login ? 'Login' : 'Create Account'),
                        ),
                      ),
                    ),
                    if (err.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFEBEE),
                          border: Border.all(color: const Color(0xFFEF9A9A)),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          err,
                          style: const TextStyle(color: Color(0xFFC62828)),
                        ),
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

class MyRequests extends StatefulWidget {
  const MyRequests({super.key, required this.state});
  final AppState state;
  @override
  State<MyRequests> createState() => _MyRequestsState();
}

class _MyRequestsState extends State<MyRequests> {
  late Future<(List<Map<String, dynamic>>, List<Map<String, dynamic>>)> f;
  int sN = 10, iN = 10;
  @override
  void initState() {
    super.initState();
    f = _load();
  }

  Future<(List<Map<String, dynamic>>, List<Map<String, dynamic>>)>
  _load() async {
    final api = Api(widget.state.apiBase, token: widget.state.token);
    return (await api.supportList(), await api.installList());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Requests')),
      body: RefreshIndicator(
        onRefresh: () async => setState(() {
          sN = 10;
          iN = 10;
          f = _load();
        }),
        child:
            FutureBuilder<
              (List<Map<String, dynamic>>, List<Map<String, dynamic>>)
            >(
              future: f,
              builder: (_, s) {
                if (s.connectionState != ConnectionState.done)
                  return const Center(child: CircularProgressIndicator());
                if (s.hasError)
                  return ListView(
                    children: [
                      const SizedBox(height: 80),
                      Center(child: Text('${s.error}')),
                    ],
                  );
                final support = (s.data?.$1 ?? []),
                    install = (s.data?.$2 ?? []);
                return ListView(
                  padding: const EdgeInsets.all(12),
                  children: [
                    const Text(
                      'Support',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    ...support
                        .take(sN)
                        .map(
                          (r) => Card(
                            child: ListTile(
                              title: Text('#${r['id']} ${r['subject']}'),
                              subtitle: Text(
                                'Status: ${r['status']}\n${r['message']}',
                              ),
                            ),
                          ),
                        ),
                    if (sN < support.length)
                      TextButton(
                        onPressed: () => setState(() => sN += 10),
                        child: const Text('Load more support'),
                      ),
                    const SizedBox(height: 8),
                    const Text(
                      'Installation',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    ...install
                        .take(iN)
                        .map(
                          (r) => Card(
                            child: ListTile(
                              title: Text('#${r['id']} ${r['subject']}'),
                              subtitle: Text(
                                'Status: ${r['status']}\nLocation: ${r['location']}',
                              ),
                            ),
                          ),
                        ),
                    if (iN < install.length)
                      TextButton(
                        onPressed: () => setState(() => iN += 10),
                        child: const Text('Load more installation'),
                      ),
                  ],
                );
              },
            ),
      ),
    );
  }
}

class _FormView extends StatefulWidget {
  const _FormView({
    required this.title,
    required this.fields,
    required this.submitText,
    required this.onSubmit,
    this.requestsTitle,
    this.loadRequests,
    this.onReply,
    this.loadReplies,
  });
  final String title;
  final List<String> fields;
  final String submitText;
  final Future<String> Function(Map<String, String>) onSubmit;
  final String? requestsTitle;
  final Future<List<Map<String, dynamic>>> Function()? loadRequests;
  final Future<String> Function(int requestId, String message)? onReply;
  final Future<List<Map<String, dynamic>>> Function(int requestId)? loadReplies;
  @override
  State<_FormView> createState() => _FormViewState();
}

class _FormViewState extends State<_FormView> {
  late final Map<String, TextEditingController> c = {
    for (final f in widget.fields) f: TextEditingController(),
  };
  bool loading = false;
  String msg = '';
  bool ok = false;
  Future<List<Map<String, dynamic>>>? rf;

  @override
  void initState() {
    super.initState();
    if (widget.loadRequests != null) {
      rf = widget.loadRequests!();
    }
  }

  Future<void> _openReplies(Map<String, dynamic> req) async {
    if (widget.loadReplies == null || widget.onReply == null) return;
    final id = _toInt(req['id']);
    final ctrl = TextEditingController();
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) {
        return Padding(
          padding: EdgeInsets.only(
            left: 12,
            right: 12,
            top: 8,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 12,
          ),
          child: FutureBuilder<List<Map<String, dynamic>>>(
            future: widget.loadReplies!(id),
            builder: (_, s) {
              final rows = s.data ?? [];
              return Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'Updates #$id',
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Flexible(
                    child: s.connectionState != ConnectionState.done
                        ? const Center(child: CircularProgressIndicator())
                        : ListView.separated(
                            shrinkWrap: true,
                            itemCount: rows.length,
                            separatorBuilder: (_, __) =>
                                const SizedBox(height: 8),
                            itemBuilder: (_, i) {
                              final r = rows[i];
                              final admin =
                                  '${r['sender_role']}'.toLowerCase() ==
                                  'admin';
                              return Align(
                                alignment: admin
                                    ? Alignment.centerLeft
                                    : Alignment.centerRight,
                                child: Container(
                                  constraints: const BoxConstraints(
                                    maxWidth: 280,
                                  ),
                                  padding: const EdgeInsets.all(10),
                                  decoration: BoxDecoration(
                                    color: admin
                                        ? const Color(0xFFEFF4FA)
                                        : const Color(0xFFFFF8E1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Text('${r['message'] ?? ''}'),
                                ),
                              );
                            },
                          ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: ctrl,
                          decoration: const InputDecoration(
                            hintText: 'Reply to this update...',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      FilledButton(
                        onPressed: () async {
                          final text = ctrl.text.trim();
                          if (text.isEmpty) return;
                          final m = await widget.onReply!(id, text);
                          if (!context.mounted) return;
                          Navigator.pop(ctx);
                          setState(() {
                            msg = m;
                            ok =
                                m.toLowerCase().contains('sent') ||
                                m.toLowerCase().contains('success');
                            if (widget.loadRequests != null) {
                              rf = widget.loadRequests!();
                            }
                          });
                        },
                        child: const Text('Send'),
                      ),
                    ],
                  ),
                ],
              );
            },
          ),
        );
      },
    );
    ctrl.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        Text(
          widget.title,
          style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 10),
        ...widget.fields.map(
          (f) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: TextField(
              controller: c[f],
              maxLines: f == 'message' || f == 'notes' ? 4 : 1,
              decoration: InputDecoration(
                labelText: f,
                border: const OutlineInputBorder(),
              ),
            ),
          ),
        ),
        FilledButton(
          onPressed: loading
              ? null
              : () async {
                  setState(() {
                    loading = true;
                    msg = '';
                    ok = false;
                  });
                  final data = {
                    for (final e in c.entries) e.key: e.value.text.trim(),
                  };
                  msg = await widget.onSubmit(data);
                  ok =
                      msg.toLowerCase().contains('created') ||
                      msg.toLowerCase().contains('success');
                  if (widget.loadRequests != null) {
                    rf = widget.loadRequests!();
                  }
                  if (mounted) setState(() => loading = false);
                },
          child: Text(loading ? 'Submitting...' : widget.submitText),
        ),
        const SizedBox(height: 8),
        if (msg.isNotEmpty)
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: ok ? const Color(0xFFE8F5E9) : const Color(0xFFFFEBEE),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(
                color: ok ? const Color(0xFFA5D6A7) : const Color(0xFFEF9A9A),
              ),
            ),
            child: Row(
              children: [
                Icon(
                  ok ? Icons.check_circle_outline : Icons.error_outline,
                  color: ok ? const Color(0xFF2E7D32) : const Color(0xFFC62828),
                ),
                const SizedBox(width: 8),
                Expanded(child: Text(msg)),
              ],
            ),
          ),
        if (widget.loadRequests != null) ...[
          const SizedBox(height: 14),
          Text(
            widget.requestsTitle ?? 'Your Requests',
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          FutureBuilder<List<Map<String, dynamic>>>(
            future: rf,
            builder: (_, s) {
              if (s.connectionState != ConnectionState.done) {
                return const Padding(
                  padding: EdgeInsets.all(12),
                  child: Center(child: CircularProgressIndicator()),
                );
              }
              if (s.hasError) return Text('${s.error}');
              final rows = s.data ?? [];
              if (rows.isEmpty) {
                return const Card(
                  child: Padding(
                    padding: EdgeInsets.all(12),
                    child: Text('No requests yet'),
                  ),
                );
              }
              return Column(
                children: rows
                    .take(10)
                    .map(
                      (r) => Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '#${r['id']} ${r['subject'] ?? ''}',
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text('Status: ${r['status']}'),
                              if ('${r['admin_response'] ?? ''}'
                                  .trim()
                                  .isNotEmpty) ...[
                                const SizedBox(height: 4),
                                Text('Admin: ${r['admin_response']}'),
                              ],
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  OutlinedButton(
                                    onPressed: () => _openReplies(r),
                                    child: const Text('View updates'),
                                  ),
                                  const SizedBox(width: 8),
                                  FilledButton(
                                    onPressed: () => _openReplies(r),
                                    child: const Text('Reply'),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                    )
                    .toList(),
              );
            },
          ),
        ],
      ],
    );
  }
}
