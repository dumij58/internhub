# Login System

## Features

- User registration with name, email, and password
- User login with email and password
- Session management
- Responsive design
- Success/error messages
- Welcome page with user's name

## How It Works

### Registration (Sign Up)

1. Click "Sign up" button
2. Fill in your name, email, and password
3. Click "Sign up" to register
4. You'll see a success message: "Thank you [Name]! Registration successful."

### Login (Sign In)

1. Click "Sign in" button (name field will be hidden)
2. Enter your email and password
3. Click "Sign in" to login
4. If credentials are correct, you'll be redirected to the homepage
5. If incorrect, you'll see: "Incorrect email or password"

### Homepage

- Displays "Hi [Name]!" with a logout button
- Only accessible to logged-in users
- Automatically redirects to login page if not authenticated

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure XAMPP MySQL service is running
   - Check database credentials in `connect.php`
   - Verify database and table exist

2. **Registration Not Working**
   - Check if the `users` table exists
   - Ensure all form fields are filled
   - Check for duplicate email addresses

3. **Login Not Working**
   - Verify email and password are correct
   - Check if user exists in database
   - Ensure password was properly hashed during registration

4. **Session Issues**
   - Make sure `session_start()` is called
   - Check if cookies are enabled in browser

## Security Notes

- Passwords are hashed using MD5 (consider using more secure hashing for production)
- Basic input validation is implemented
- Session-based authentication is used
- SQL injection protection should be enhanced for production use

## Browser Compatibility

- Works on all modern browsers
- Responsive design for mobile devices
- Requires JavaScript enabled for form switching
