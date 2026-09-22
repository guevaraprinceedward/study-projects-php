import java.sql.*;
import java.util.*;
import java.util.List;
import java.awt.*;
import java.awt.event.*;
import javax.swing.*;
import javax.swing.border.*;
import javax.swing.table.DefaultTableModel;
import java.text.NumberFormat;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

/* =====================================================================
   AyosCoffeeNegosyo POS System
   Java IV - Object-Oriented Programming Project

   Pinapakita ng file na ito ang mga pangunahing OOP concepts:
     - ENCAPSULATION : private fields + public getters/setters (Product, User, CartItem)
     - INHERITANCE   : User -> Admin, Cashier
     - POLYMORPHISM  : overridden getDashboardTitle() / getPermissions()
     - ABSTRACTION   : abstract class User, interface Receiptable

   Database: MySQL (gamit ang JDBC) - kumokonekta sa parehong database
   na ginagamit ng PHP website (AyosCoffeeNegosyo).
   ===================================================================== */


/* =====================================================================
   1. DATABASE CONFIG
   ===================================================================== */
class DBConfig {
    // ── PALITAN ITO ng tamang credentials ng MySQL database mo ──
    static final String HOST     = "localhost";
    static final String PORT     = "3306";
    static final String DB_NAME  = "ayoscoffeenegosyo";
    static final String USER     = "root";
    static final String PASSWORD = "";

    static final String URL =
        "jdbc:mysql://" + HOST + ":" + PORT + "/" + DB_NAME +
        "?useSSL=false&allowPublicKeyRetrieval=true&serverTimezone=UTC";

    public static Connection getConnection() throws SQLException {
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException e) {
            throw new SQLException("MySQL JDBC Driver hindi nahanap. Tingnan ang README_SETUP.md", e);
        }
        return DriverManager.getConnection(URL, USER, PASSWORD);
    }
}


/* =====================================================================
   2. INTERFACE - ABSTRACTION
   ===================================================================== */
interface Receiptable {
    String generateReceipt();
}


/* =====================================================================
   3. ABSTRACT CLASS - User (base class para sa inheritance)
   ===================================================================== */
abstract class User {
    // ── ENCAPSULATION: private fields ──
    private int id;
    private String name;
    private String email;
    private String role;

    public User(int id, String name, String email, String role) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.role = role;
    }

    // Getters
    public int getId()       { return id; }
    public String getName()  { return name; }
    public String getEmail() { return email; }
    public String getRole()  { return role; }

    // ── POLYMORPHISM: abstract methods na ibang implementation per subclass ──
    public abstract String getDashboardTitle();
    public abstract List<String> getPermissions();

    @Override
    public String toString() {
        return name + " (" + role + ")";
    }
}

/* ── INHERITANCE: Admin extends User ── */
class Admin extends User {
    public Admin(int id, String name, String email) {
        super(id, name, email, "admin");
    }

    @Override
    public String getDashboardTitle() {
        return "Admin Control Panel";
    }

    @Override
    public List<String> getPermissions() {
        return Arrays.asList("PROCESS_SALE", "VOID_TRANSACTION", "VIEW_REPORTS", "MANAGE_STOCK");
    }
}

/* ── INHERITANCE: Cashier extends User ── */
class Cashier extends User {
    public Cashier(int id, String name, String email) {
        super(id, name, email, "cashier");
    }

    @Override
    public String getDashboardTitle() {
        return "Cashier Terminal";
    }

    @Override
    public List<String> getPermissions() {
        return Arrays.asList("PROCESS_SALE");
    }
}


/* =====================================================================
   4. PRODUCT CLASS - ENCAPSULATION
   ===================================================================== */
class Product {
    private int id;
    private String name;
    private String category;
    private String sku;
    private double price;
    private int stock;
    private int reorderLevel;

    public Product(int id, String name, String category, String sku,
                    double price, int stock, int reorderLevel) {
        this.id = id;
        this.name = name;
        this.category = category;
        this.sku = sku;
        this.price = price;
        this.stock = stock;
        this.reorderLevel = reorderLevel;
    }

    public int getId()             { return id; }
    public String getName()        { return name; }
    public String getCategory()    { return category; }
    public String getSku()         { return sku; }
    public double getPrice()       { return price; }
    public int getStock()          { return stock; }
    public int getReorderLevel()   { return reorderLevel; }

    public void setStock(int stock) { this.stock = stock; }

    public boolean isLowStock() {
        return stock <= reorderLevel;
    }

    @Override
    public String toString() {
        return name;
    }
}


/* =====================================================================
   5. CART ITEM CLASS
   ===================================================================== */
class CartItem {
    private Product product;
    private int quantity;

    public CartItem(Product product, int quantity) {
        this.product = product;
        this.quantity = quantity;
    }

    public Product getProduct() { return product; }
    public int getQuantity()    { return quantity; }
    public void setQuantity(int quantity) { this.quantity = quantity; }
    public void addQuantity(int amount)   { this.quantity += amount; }

    public double getSubtotal() {
        return product.getPrice() * quantity;
    }
}


/* =====================================================================
   6. CART CLASS - implements Receiptable (ABSTRACTION / interface)
   ===================================================================== */
class Cart implements Receiptable {
    private List<CartItem> items = new ArrayList<>();
    private static final double VAT_RATE = 0.12; // 12% VAT (PH standard)

    public void addProduct(Product product, int qty) {
        for (CartItem item : items) {
            if (item.getProduct().getId() == product.getId()) {
                item.addQuantity(qty);
                return;
            }
        }
        items.add(new CartItem(product, qty));
    }

    public void removeItem(int index) {
        if (index >= 0 && index < items.size()) {
            items.remove(index);
        }
    }

    public void clear() {
        items.clear();
    }

    public List<CartItem> getItems() {
        return items;
    }

    public double getSubtotal() {
        double sum = 0;
        for (CartItem item : items) sum += item.getSubtotal();
        return sum;
    }

    public double getVat() {
        return getSubtotal() * VAT_RATE / (1 + VAT_RATE); // VAT-inclusive computation
    }

    public double getTotal() {
        return getSubtotal();
    }

    public boolean isEmpty() {
        return items.isEmpty();
    }

    // ── POLYMORPHISM: implementation ng interface method ──
    @Override
    public String generateReceipt() {
        NumberFormat peso = NumberFormat.getCurrencyInstance(new Locale("en", "PH"));
        StringBuilder sb = new StringBuilder();
        sb.append("====================================\n");
        sb.append("      AYOSCOFFEE NEGOSYO\n");
        sb.append("          OFFICIAL RECEIPT\n");
        sb.append("====================================\n");
        sb.append(DateTimeFormatter.ofPattern("MMM dd, yyyy hh:mm a")
                .format(LocalDateTime.now())).append("\n");
        sb.append("------------------------------------\n");
        for (CartItem item : items) {
            sb.append(String.format("%-20s x%d%n", item.getProduct().getName(), item.getQuantity()));
            sb.append(String.format("  %s%n", peso.format(item.getSubtotal())));
        }
        sb.append("------------------------------------\n");
        sb.append(String.format("Subtotal (VAT incl.): %s%n", peso.format(getSubtotal())));
        sb.append(String.format("VAT (12%%):            %s%n", peso.format(getVat())));
        sb.append(String.format("TOTAL:                %s%n", peso.format(getTotal())));
        sb.append("====================================\n");
        sb.append("     Salamat sa inyong pagbili!\n");
        sb.append("====================================\n");
        return sb.toString();
    }
}


/* =====================================================================
   7. DAO (Data Access Object) CLASSES - JDBC queries
   ===================================================================== */
class UserDAO {
    /**
     * Sinusuri ang login credentials laban sa 'users' table.
     * NOTE: Kung BCrypt-hashed (mula sa PHP password_hash), kailangan ng
     * jBCrypt library. Tingnan ang README_SETUP.md para sa setup.
     */
    public User login(String email, String plainPassword) throws SQLException {
        String sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        try (Connection conn = DBConfig.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {

            ps.setString(1, email);
            ResultSet rs = ps.executeQuery();

            if (rs.next()) {
                String hashedPassword = rs.getString("password");
                boolean valid = PasswordUtil.verify(plainPassword, hashedPassword);

                if (valid) {
                    int id = rs.getInt("id");
                    String name = rs.getString("name");
                    String role = rs.getString("role") != null ? rs.getString("role") : "cashier";

                    if (role.equalsIgnoreCase("admin")) {
                        return new Admin(id, name, email);
                    } else {
                        return new Cashier(id, name, email);
                    }
                }
            }
            return null; // walang match
        }
    }
}

class ProductDAO {
    public List<Product> getAllProducts() throws SQLException {
        List<Product> products = new ArrayList<>();
        String sql = "SELECT id, name, category, sku, price, stock, reorder_level FROM products ORDER BY name ASC";

        try (Connection conn = DBConfig.getConnection();
             Statement st = conn.createStatement();
             ResultSet rs = st.executeQuery(sql)) {

            while (rs.next()) {
                products.add(new Product(
                    rs.getInt("id"),
                    rs.getString("name"),
                    rs.getString("category") != null ? rs.getString("category") : "general",
                    rs.getString("sku"),
                    rs.getDouble("price"),
                    rs.getInt("stock"),
                    rs.getInt("reorder_level")
                ));
            }
        }
        return products;
    }

    /** I-deduct ang stock pagkatapos magbayad. */
    public void deductStock(int productId, int qty) throws SQLException {
        String sql = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
        try (Connection conn = DBConfig.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, qty);
            ps.setInt(2, productId);
            ps.setInt(3, qty);
            ps.executeUpdate();
        }
    }
}

class OrderDAO {
    /**
     * Nire-record ang transaction sa orders / order_items table.
     * Ginagamit ang Connection.setAutoCommit(false) para sa TRANSACTION SAFETY -
     * kung may mag-fail na isang query, mag-rorollback lahat (walang
     * "kalahating" na-save na order).
     */
    public int recordSale(Cart cart, int userId) throws SQLException {
        String insertOrder = "INSERT INTO orders (user_id, status, created_at) VALUES (?, 'completed', NOW())";
        String insertItem  = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";

        Connection conn = null;
        try {
            conn = DBConfig.getConnection();
            conn.setAutoCommit(false);

            int orderId;
            try (PreparedStatement ps = conn.prepareStatement(insertOrder, Statement.RETURN_GENERATED_KEYS)) {
                ps.setInt(1, userId);
                ps.executeUpdate();
                ResultSet keys = ps.getGeneratedKeys();
                keys.next();
                orderId = keys.getInt(1);
            }

            try (PreparedStatement ps = conn.prepareStatement(insertItem)) {
                for (CartItem item : cart.getItems()) {
                    ps.setInt(1, orderId);
                    ps.setInt(2, item.getProduct().getId());
                    ps.setInt(3, item.getQuantity());
                    ps.setDouble(4, item.getProduct().getPrice());
                    ps.addBatch();
                }
                ps.executeBatch();
            }

            ProductDAO productDAO = new ProductDAO();
            for (CartItem item : cart.getItems()) {
                productDAO.deductStock(item.getProduct().getId(), item.getQuantity());
            }

            conn.commit();
            return orderId;

        } catch (SQLException e) {
            if (conn != null) conn.rollback();
            throw e;
        } finally {
            if (conn != null) {
                conn.setAutoCommit(true);
                conn.close();
            }
        }
    }
}


/* =====================================================================
   8. PASSWORD UTILITY
   ===================================================================== */
class PasswordUtil {
    /**
     * Sinusuri ang password. Sinusuportahan ang plain-text comparison
     * para sa simpleng setup. Para sa PHP bcrypt hashes (password_hash),
     * idagdag ang jBCrypt library at gamitin ang BCrypt.checkpw().
     * Tingnan ang README_SETUP.md.
     */
    public static boolean verify(String plainPassword, String storedPassword) {
        if (storedPassword.startsWith("$2y$") || storedPassword.startsWith("$2a$") || storedPassword.startsWith("$2b$")) {
            // PHP-style bcrypt hash - kailangan ng jBCrypt
            try {
                Class<?> bcryptClass = Class.forName("org.mindrot.jbcrypt.BCrypt");
                java.lang.reflect.Method checkpw = bcryptClass.getMethod("checkpw", String.class, String.class);
                return (boolean) checkpw.invoke(null, plainPassword, storedPassword);
            } catch (Exception e) {
                System.err.println("jBCrypt library hindi nahanap. Tingnan README_SETUP.md.");
                return false;
            }
        }
        // Plain text fallback (kung hindi naka-hash ang password sa DB mo)
        return plainPassword.equals(storedPassword);
    }
}


/* =====================================================================
   9. LOGIN FRAME (Swing GUI)
   ===================================================================== */
class LoginFrame extends JFrame {
    private JTextField emailField;
    private JPasswordField passwordField;
    private static final Color BG_DARK = new Color(11, 11, 9);
    private static final Color CARD = new Color(26, 26, 22);
    private static final Color GOLD = new Color(201, 168, 76);
    private static final Color TEXT = new Color(232, 228, 216);
    private static final Color MUTED = new Color(107, 107, 88);

    public LoginFrame() {
        setTitle("AyosCoffeeNegosyo POS - Login");
        setSize(420, 480);
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setLocationRelativeTo(null);
        setResizable(false);
        getContentPane().setBackground(BG_DARK);
        setLayout(new GridBagLayout());

        JPanel card = new JPanel();
        card.setBackground(CARD);
        card.setBorder(new EmptyBorder(36, 36, 36, 36));
        card.setLayout(new BoxLayout(card, BoxLayout.Y_AXIS));

        JLabel title = new JLabel("AyosCoffee POS");
        title.setFont(new Font("Serif", Font.BOLD, 26));
        title.setForeground(GOLD);
        title.setAlignmentX(Component.CENTER_ALIGNMENT);

        JLabel subtitle = new JLabel("Sign in to continue");
        subtitle.setFont(new Font("SansSerif", Font.PLAIN, 12));
        subtitle.setForeground(MUTED);
        subtitle.setAlignmentX(Component.CENTER_ALIGNMENT);
        subtitle.setBorder(new EmptyBorder(4, 0, 24, 0));

        JLabel emailLabel = makeLabel("Email");
        emailField = new JTextField();
        styleField(emailField);

        JLabel passLabel = makeLabel("Password");
        passwordField = new JPasswordField();
        styleField(passwordField);
        passwordField.setBorder(emailField.getBorder());

        JButton loginBtn = new JButton("LOG IN");
        loginBtn.setAlignmentX(Component.CENTER_ALIGNMENT);
        loginBtn.setMaximumSize(new Dimension(Integer.MAX_VALUE, 42));
        loginBtn.setBackground(GOLD);
        loginBtn.setForeground(Color.BLACK);
        loginBtn.setFont(new Font("SansSerif", Font.BOLD, 13));
        loginBtn.setFocusPainted(false);
        loginBtn.setBorder(new EmptyBorder(10, 10, 10, 10));
        loginBtn.addActionListener(e -> attemptLogin());

        passwordField.addActionListener(e -> attemptLogin());

        JLabel hint = new JLabel("<html><center>Default: admin@ayoscoffee.com / admin123</center></html>");
        hint.setFont(new Font("SansSerif", Font.PLAIN, 10));
        hint.setForeground(MUTED);
        hint.setAlignmentX(Component.CENTER_ALIGNMENT);
        hint.setBorder(new EmptyBorder(16, 0, 0, 0));

        card.add(title);
        card.add(subtitle);
        card.add(emailLabel);
        card.add(Box.createVerticalStrut(4));
        card.add(emailField);
        card.add(Box.createVerticalStrut(16));
        card.add(passLabel);
        card.add(Box.createVerticalStrut(4));
        card.add(passwordField);
        card.add(Box.createVerticalStrut(24));
        card.add(loginBtn);
        card.add(hint);

        add(card);
    }

    private JLabel makeLabel(String text) {
        JLabel l = new JLabel(text);
        l.setFont(new Font("SansSerif", Font.PLAIN, 11));
        l.setForeground(MUTED);
        l.setAlignmentX(Component.LEFT_ALIGNMENT);
        return l;
    }

    private void styleField(JTextField field) {
        field.setMaximumSize(new Dimension(Integer.MAX_VALUE, 38));
        field.setBackground(new Color(19, 19, 16));
        field.setForeground(TEXT);
        field.setCaretColor(TEXT);
        field.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(new Color(44, 44, 36)),
                new EmptyBorder(8, 10, 8, 10)));
        field.setAlignmentX(Component.LEFT_ALIGNMENT);
    }

    private void attemptLogin() {
        String email = emailField.getText().trim();
        String password = new String(passwordField.getPassword());

        if (email.isEmpty() || password.isEmpty()) {
            JOptionPane.showMessageDialog(this, "Punan ang email at password.", "Kulang na Field", JOptionPane.WARNING_MESSAGE);
            return;
        }

        try {
            UserDAO userDAO = new UserDAO();
            User user = userDAO.login(email, password);

            if (user != null) {
                dispose();
                SwingUtilities.invokeLater(() -> new POSFrame(user).setVisible(true));
            } else {
                JOptionPane.showMessageDialog(this, "Maling email o password.", "Login Failed", JOptionPane.ERROR_MESSAGE);
            }
        } catch (SQLException ex) {
            JOptionPane.showMessageDialog(this,
                "Hindi makakonekta sa database:\n" + ex.getMessage(),
                "Database Error", JOptionPane.ERROR_MESSAGE);
        }
    }
}


/* =====================================================================
   10. MAIN POS FRAME (Swing GUI)
   ===================================================================== */
class POSFrame extends JFrame {
    private User currentUser;
    private Cart cart = new Cart();
    private List<Product> allProducts = new ArrayList<>();

    private DefaultTableModel productTableModel;
    private DefaultTableModel cartTableModel;
    private JTable productTable;
    private JTable cartTable;
    private JLabel totalLabel;
    private JLabel vatLabel;
    private JTextField searchField;
    private JTextField qtyField;

    private static final Color BG_DARK = new Color(11, 11, 9);
    private static final Color CARD = new Color(26, 26, 22);
    private static final Color BORDER = new Color(44, 44, 36);
    private static final Color GOLD = new Color(201, 168, 76);
    private static final Color GREEN = new Color(106, 170, 82);
    private static final Color RED = new Color(192, 57, 43);
    private static final Color TEXT = new Color(232, 228, 216);
    private static final Color MUTED = new Color(107, 107, 88);

    public POSFrame(User user) {
        this.currentUser = user;
        setTitle("AyosCoffeeNegosyo POS - " + user.getDashboardTitle());
        setSize(1100, 680);
        setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        setLocationRelativeTo(null);
        getContentPane().setBackground(BG_DARK);
        setLayout(new BorderLayout(0, 0));

        add(buildTopBar(), BorderLayout.NORTH);
        add(buildCenterPanel(), BorderLayout.CENTER);

        loadProducts();
    }

    /* ── TOP BAR ── */
    private JPanel buildTopBar() {
        JPanel bar = new JPanel(new BorderLayout());
        bar.setBackground(CARD);
        bar.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER),
                new EmptyBorder(14, 24, 14, 24)));

        JLabel titleLabel = new JLabel("AyosCoffee POS");
        titleLabel.setFont(new Font("Serif", Font.BOLD, 20));
        titleLabel.setForeground(GOLD);

        JLabel userLabel = new JLabel(currentUser.toString() + "   |   " + currentUser.getDashboardTitle());
        userLabel.setFont(new Font("SansSerif", Font.PLAIN, 12));
        userLabel.setForeground(MUTED);

        JButton logoutBtn = new JButton("Logout");
        logoutBtn.setBackground(new Color(192, 57, 43, 40));
        logoutBtn.setForeground(new Color(224, 90, 90));
        logoutBtn.setFocusPainted(false);
        logoutBtn.setBorder(BorderFactory.createLineBorder(RED));
        logoutBtn.addActionListener(e -> {
            dispose();
            SwingUtilities.invokeLater(() -> new LoginFrame().setVisible(true));
        });

        JPanel left = new JPanel(new GridLayout(2, 1));
        left.setOpaque(false);
        left.add(titleLabel);
        left.add(userLabel);

        bar.add(left, BorderLayout.WEST);
        bar.add(logoutBtn, BorderLayout.EAST);
        return bar;
    }

    /* ── CENTER PANEL: products (left) + cart (right) ── */
    private JPanel buildCenterPanel() {
        JPanel center = new JPanel(new BorderLayout(16, 0));
        center.setBackground(BG_DARK);
        center.setBorder(new EmptyBorder(20, 20, 20, 20));

        center.add(buildProductPanel(), BorderLayout.CENTER);
        center.add(buildCartPanel(), BorderLayout.EAST);
        return center;
    }

    private JPanel buildProductPanel() {
        JPanel panel = new JPanel(new BorderLayout(0, 10));
        panel.setBackground(BG_DARK);

        searchField = new JTextField();
        searchField.setBackground(CARD);
        searchField.setForeground(TEXT);
        searchField.setCaretColor(TEXT);
        searchField.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER),
                new EmptyBorder(8, 12, 8, 12)));
        searchField.putClientProperty("JTextField.placeholderText", "Search product...");
        searchField.getDocument().addDocumentListener(new javax.swing.event.DocumentListener() {
            public void insertUpdate(javax.swing.event.DocumentEvent e) { filterProducts(); }
            public void removeUpdate(javax.swing.event.DocumentEvent e) { filterProducts(); }
            public void changedUpdate(javax.swing.event.DocumentEvent e) { filterProducts(); }
        });

        productTableModel = new DefaultTableModel(
                new String[]{"ID", "Name", "Category", "Price", "Stock"}, 0) {
            @Override public boolean isCellEditable(int row, int col) { return false; }
        };
        productTable = new JTable(productTableModel);
        styleTable(productTable);
        productTable.getColumnModel().getColumn(0).setMaxWidth(50);

        JScrollPane scrollPane = new JScrollPane(productTable);
        scrollPane.setBorder(BorderFactory.createLineBorder(BORDER));
        scrollPane.getViewport().setBackground(CARD);

        JPanel addRow = new JPanel(new BorderLayout(8, 0));
        addRow.setOpaque(false);
        addRow.setBorder(new EmptyBorder(10, 0, 0, 0));

        qtyField = new JTextField("1");
        qtyField.setPreferredSize(new Dimension(60, 36));
        qtyField.setBackground(CARD);
        qtyField.setForeground(TEXT);
        qtyField.setHorizontalAlignment(JTextField.CENTER);
        qtyField.setBorder(BorderFactory.createLineBorder(BORDER));

        JButton addBtn = new JButton("Add to Cart");
        addBtn.setBackground(GREEN);
        addBtn.setForeground(Color.WHITE);
        addBtn.setFocusPainted(false);
        addBtn.setBorder(new EmptyBorder(8, 16, 8, 16));
        addBtn.addActionListener(e -> addSelectedToCart());

        JPanel qtyWrap = new JPanel(new BorderLayout(8, 0));
        qtyWrap.setOpaque(false);
        JLabel qtyLbl = new JLabel("Qty:");
        qtyLbl.setForeground(MUTED);
        qtyWrap.add(qtyLbl, BorderLayout.WEST);
        qtyWrap.add(qtyField, BorderLayout.CENTER);

        addRow.add(qtyWrap, BorderLayout.WEST);
        addRow.add(addBtn, BorderLayout.EAST);

        panel.add(searchField, BorderLayout.NORTH);
        panel.add(scrollPane, BorderLayout.CENTER);
        panel.add(addRow, BorderLayout.SOUTH);
        return panel;
    }

    private JPanel buildCartPanel() {
        JPanel panel = new JPanel(new BorderLayout(0, 10));
        panel.setPreferredSize(new Dimension(380, 0));
        panel.setBackground(BG_DARK);

        JLabel cartTitle = new JLabel("Current Order");
        cartTitle.setFont(new Font("Serif", Font.BOLD, 16));
        cartTitle.setForeground(TEXT);

        cartTableModel = new DefaultTableModel(new String[]{"Item", "Qty", "Subtotal"}, 0) {
            @Override public boolean isCellEditable(int row, int col) { return false; }
        };
        cartTable = new JTable(cartTableModel);
        styleTable(cartTable);

        JScrollPane cartScroll = new JScrollPane(cartTable);
        cartScroll.setBorder(BorderFactory.createLineBorder(BORDER));
        cartScroll.getViewport().setBackground(CARD);

        JButton removeBtn = new JButton("Remove Selected");
        removeBtn.setBackground(new Color(192, 57, 43, 30));
        removeBtn.setForeground(new Color(224, 90, 90));
        removeBtn.setFocusPainted(false);
        removeBtn.setBorder(BorderFactory.createLineBorder(RED));
        removeBtn.addActionListener(e -> removeSelectedFromCart());

        JPanel summary = new JPanel();
        summary.setLayout(new BoxLayout(summary, BoxLayout.Y_AXIS));
        summary.setBackground(CARD);
        summary.setBorder(new EmptyBorder(16, 18, 16, 18));

        vatLabel = new JLabel("VAT (12% incl.): ₱0.00");
        vatLabel.setForeground(MUTED);
        vatLabel.setFont(new Font("SansSerif", Font.PLAIN, 12));
        vatLabel.setAlignmentX(Component.LEFT_ALIGNMENT);

        totalLabel = new JLabel("TOTAL: ₱0.00");
        totalLabel.setForeground(GOLD);
        totalLabel.setFont(new Font("Serif", Font.BOLD, 24));
        totalLabel.setAlignmentX(Component.LEFT_ALIGNMENT);
        totalLabel.setBorder(new EmptyBorder(4, 0, 12, 0));

        JButton checkoutBtn = new JButton("CHECKOUT & PRINT RECEIPT");
        checkoutBtn.setBackground(GOLD);
        checkoutBtn.setForeground(Color.BLACK);
        checkoutBtn.setFont(new Font("SansSerif", Font.BOLD, 13));
        checkoutBtn.setFocusPainted(false);
        checkoutBtn.setAlignmentX(Component.LEFT_ALIGNMENT);
        checkoutBtn.setMaximumSize(new Dimension(Integer.MAX_VALUE, 44));
        checkoutBtn.setBorder(new EmptyBorder(10, 10, 10, 10));
        checkoutBtn.addActionListener(e -> checkout());

        summary.add(vatLabel);
        summary.add(totalLabel);
        summary.add(checkoutBtn);

        panel.add(cartTitle, BorderLayout.NORTH);
        panel.add(cartScroll, BorderLayout.CENTER);

        JPanel bottom = new JPanel(new BorderLayout(0, 10));
        bottom.setOpaque(false);
        bottom.add(removeBtn, BorderLayout.NORTH);
        bottom.add(summary, BorderLayout.SOUTH);
        panel.add(bottom, BorderLayout.SOUTH);

        return panel;
    }

    private void styleTable(JTable table) {
        table.setBackground(CARD);
        table.setForeground(TEXT);
        table.setGridColor(BORDER);
        table.setRowHeight(28);
        table.setSelectionBackground(GOLD.darker());
        table.setSelectionForeground(Color.WHITE);
        table.getTableHeader().setBackground(new Color(19, 19, 16));
        table.getTableHeader().setForeground(MUTED);
        table.setFont(new Font("SansSerif", Font.PLAIN, 12));
    }

    /* ── DATA LOADING ── */
    private void loadProducts() {
        try {
            ProductDAO dao = new ProductDAO();
            allProducts = dao.getAllProducts();
            refreshProductTable(allProducts);
        } catch (SQLException ex) {
            JOptionPane.showMessageDialog(this,
                "Hindi makuha ang listahan ng produkto:\n" + ex.getMessage(),
                "Database Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private void refreshProductTable(List<Product> products) {
        productTableModel.setRowCount(0);
        NumberFormat peso = NumberFormat.getCurrencyInstance(new Locale("en", "PH"));
        for (Product p : products) {
            productTableModel.addRow(new Object[]{
                p.getId(), p.getName(), p.getCategory(), peso.format(p.getPrice()),
                p.getStock() + (p.isLowStock() ? " ⚠" : "")
            });
        }
    }

    private void filterProducts() {
        String query = searchField.getText().trim().toLowerCase();
        if (query.isEmpty()) {
            refreshProductTable(allProducts);
            return;
        }
        List<Product> filtered = new ArrayList<>();
        for (Product p : allProducts) {
            if (p.getName().toLowerCase().contains(query) ||
                p.getCategory().toLowerCase().contains(query)) {
                filtered.add(p);
            }
        }
        refreshProductTable(filtered);
    }

    /* ── CART ACTIONS ── */
    private void addSelectedToCart() {
        int row = productTable.getSelectedRow();
        if (row < 0) {
            JOptionPane.showMessageDialog(this, "Pumili muna ng produkto.", "Walang Napili", JOptionPane.WARNING_MESSAGE);
            return;
        }

        int productId = (int) productTableModel.getValueAt(row, 0);
        Product selected = null;
        for (Product p : allProducts) {
            if (p.getId() == productId) { selected = p; break; }
        }
        if (selected == null) return;

        int qty;
        try {
            qty = Integer.parseInt(qtyField.getText().trim());
            if (qty <= 0) throw new NumberFormatException();
        } catch (NumberFormatException ex) {
            JOptionPane.showMessageDialog(this, "Maling quantity.", "Error", JOptionPane.WARNING_MESSAGE);
            return;
        }

        if (qty > selected.getStock()) {
            JOptionPane.showMessageDialog(this,
                "Hindi sapat ang stock. Available: " + selected.getStock(),
                "Kulang na Stock", JOptionPane.WARNING_MESSAGE);
            return;
        }

        cart.addProduct(selected, qty);
        refreshCartTable();
    }

    private void removeSelectedFromCart() {
        int row = cartTable.getSelectedRow();
        if (row < 0) return;
        cart.removeItem(row);
        refreshCartTable();
    }

    private void refreshCartTable() {
        cartTableModel.setRowCount(0);
        NumberFormat peso = NumberFormat.getCurrencyInstance(new Locale("en", "PH"));
        for (CartItem item : cart.getItems()) {
            cartTableModel.addRow(new Object[]{
                item.getProduct().getName(), item.getQuantity(), peso.format(item.getSubtotal())
            });
        }
        vatLabel.setText("VAT (12% incl.): " + peso.format(cart.getVat()));
        totalLabel.setText("TOTAL: " + peso.format(cart.getTotal()));
    }

    /* ── CHECKOUT ── */
    private void checkout() {
        if (cart.isEmpty()) {
            JOptionPane.showMessageDialog(this, "Walang laman ang cart.", "Walang Laman", JOptionPane.WARNING_MESSAGE);
            return;
        }

        int confirm = JOptionPane.showConfirmDialog(this,
            "Kumpirmahin ang transaksyon na ito?",
            "Confirm Checkout", JOptionPane.YES_NO_OPTION);
        if (confirm != JOptionPane.YES_OPTION) return;

        try {
            OrderDAO orderDAO = new OrderDAO();
            int orderId = orderDAO.recordSale(cart, currentUser.getId());

            String receipt = cart.generateReceipt(); // POLYMORPHIC call via Receiptable

            JTextArea receiptArea = new JTextArea(receipt);
            receiptArea.setEditable(false);
            receiptArea.setFont(new Font("Monospaced", Font.PLAIN, 12));
            JOptionPane.showMessageDialog(this, new JScrollPane(receiptArea),
                "Order #" + orderId + " - Receipt", JOptionPane.INFORMATION_MESSAGE);

            cart.clear();
            refreshCartTable();
            loadProducts(); // i-refresh ang stock display

        } catch (SQLException ex) {
            JOptionPane.showMessageDialog(this,
                "Nabigo ang transaksyon:\n" + ex.getMessage(),
                "Checkout Error", JOptionPane.ERROR_MESSAGE);
        }
    }
}


/* =====================================================================
   11. MAIN CLASS - Entry point
   ===================================================================== */
public class POSSystem {
    public static void main(String[] args) {
        try {
            UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
        } catch (Exception ignored) {}

        SwingUtilities.invokeLater(() -> new LoginFrame().setVisible(true));
    }
}