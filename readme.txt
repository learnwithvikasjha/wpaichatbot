=== AI Chatbot for WordPress ===
Contributors: aichatbot
Tags: chatbot, ai, openai, woocommerce, customer-service, support, chat, gpt, artificial-intelligence, ecommerce
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform your WordPress site with an intelligent AI-powered chatbot featuring WooCommerce integration, comprehensive chat history, and advanced user empowerment tools.

== Description ==

**AI Chatbot for WordPress** is a powerful, feature-rich chatbot plugin that leverages OpenAI's advanced language models to provide intelligent customer support and enhance user experience on your WordPress website.

= 🤖 **Key Features** =

**AI-Powered Conversations**
* Multiple OpenAI model support (GPT-4o, GPT-4o mini, GPT-3.5 Turbo, etc.)
* Dynamic function calling - AI can query data when needed
* Intelligent data retrieval for orders, products, and store info
* User empowerment tools - Help, suggestions, and conversation guidance
* Automatic parameter optimization for each model
* Model comparison and recommendations in admin

**WooCommerce Integration**
* Dynamic order queries - AI can fetch user orders when asked
* Real-time product searches - AI can search and filter products
* Live store information - AI can access current store data
* Smart data filtering - Support for status, category, and stock filters
* User-specific data - Orders and preferences for logged-in users

**Comprehensive Chat History**
* Complete conversation logging with detailed insights
* Context tracking - see exactly what was sent to OpenAI
* User session management with IP tracking
* Advanced filtering by user, date, and message type
* Admin dashboard for monitoring and analysis

**User Empowerment Tools**
* Interactive help system with suggestions and quick actions
* Conversation guidance and topic suggestions
* Available actions discovery - "What can you help me with?"
* Conversation summaries and recaps
* Quick action buttons for common tasks
* Comprehensive FAQ and help topic search
* User preferences management
* Context-aware conversation suggestions

**Advanced User Management**
* Automatic user identification for logged-in users
* Unique guest identification using session and IP tracking
* Guest user separation - each guest gets unique tracking
* User preference storage and retrieval
* Personalized responses based on user history

**Security & Privacy**
* Admin-only access to chat history (requires `manage_options`)
* Nonce verification for all AJAX requests
* Data sanitization before database storage
* IP address logging for security monitoring
* Session tracking for conversation grouping

= 🛒 **Perfect for E-commerce** =

This plugin is specifically designed for WooCommerce stores, providing:

* **Order Management**: Users can ask about their orders, track packages, and get order history
* **Product Discovery**: AI can search products, check availability, and provide recommendations
* **Customer Support**: Comprehensive help system with FAQs and support ticket creation
* **Personalization**: User-specific data and preferences for better experience
* **Analytics**: Detailed chat history for understanding customer needs

= 🎯 **Use Cases** =

* **Customer Support**: Handle common questions and provide instant assistance
* **Product Discovery**: Help customers find products and get recommendations
* **Order Management**: Allow customers to check order status and history
* **Lead Generation**: Engage visitors and convert them to customers
* **Data Collection**: Understand customer needs through conversation analysis

= ⚡ **Easy Setup** =

1. Upload and activate the plugin
2. Add your OpenAI API key
3. Select your preferred AI model
4. Enable WooCommerce integration (optional)
5. Customize settings as needed

The chatbot automatically appears on all pages and starts helping your customers immediately!

= 🔧 **Technical Features** =

* **Function Calling**: AI dynamically queries data when needed instead of pre-built context
* **Database Optimization**: Proper indexing and performance improvements
* **Automatic Upgrades**: Database schema automatically updates when needed
* **Error Logging**: Comprehensive debugging and error tracking
* **API Integration**: Seamless OpenAI API integration with retry logic
* **Responsive Design**: Works perfectly on desktop and mobile devices

= 📊 **Admin Dashboard** =

* **Chat History**: View all conversations with advanced filtering
* **User Analytics**: Track user behavior and conversation patterns
* **Model Management**: Compare and select optimal AI models
* **Settings Management**: Easy configuration and customization
* **Performance Monitoring**: Track API usage and response times

= 🌟 **Why Choose This Plugin?** =

* **Intelligent**: Uses advanced AI models for natural conversations
* **Comprehensive**: Full-featured solution with extensive capabilities
* **User-Friendly**: Easy setup and intuitive admin interface
* **Secure**: Built with WordPress security best practices
* **Scalable**: Handles high traffic and large datasets efficiently
* **Customizable**: Flexible settings and configuration options

Transform your WordPress site into an intelligent, AI-powered customer service platform today!

== Installation ==

1. Upload the `aichatbot` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **AI Chatbot → Settings** to configure the plugin
4. Add your OpenAI API key and select your preferred model
5. Enable WooCommerce integration if desired
6. The chat widget will automatically appear on your site

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* OpenAI API key
* WooCommerce plugin (optional, for e-commerce features)

== Frequently Asked Questions ==

= How do I get an OpenAI API key? =

1. Visit [OpenAI's API page](https://platform.openai.com/api-keys)
2. Create an account or sign in
3. Navigate to the API keys section
4. Create a new API key
5. Copy the key and paste it in the plugin settings

= Which OpenAI model should I use? =

* **GPT-4o mini**: Best value for most use cases
* **GPT-4o**: Best performance for complex queries
* **GPT-3.5 Turbo**: Most cost-effective for simple questions

The plugin includes a model comparison table to help you choose.

= Does this work with WooCommerce? =

Yes! The plugin has full WooCommerce integration. Users can ask about their orders, search products, and get store information. The AI can access real-time data from your WooCommerce store.

= How much does it cost? =

The plugin is free, but you'll need to pay for OpenAI API usage. Costs depend on the model you choose and how much you use it. GPT-3.5 Turbo is the most cost-effective option.

= Can I customize the chat widget appearance? =

Yes! The plugin includes CSS files that you can customize to match your site's design. The chat widget is fully responsive and works on all devices.

= Is my data secure? =

Absolutely! The plugin follows WordPress security best practices:
* All data is sanitized before storage
* Nonce verification for all requests
* Admin-only access to sensitive data
* Secure API key storage

= Can I export chat history? =

Yes! The admin dashboard includes comprehensive chat history with filtering and export capabilities. You can view conversations, analyze user behavior, and export data for further analysis.

= Does it work on mobile devices? =

Yes! The chat widget is fully responsive and works perfectly on mobile devices, tablets, and desktop computers.

= Can I limit which pages show the chat widget? =

Yes! The plugin includes options to control where the chat widget appears. You can exclude specific pages or post types if needed.

= How do I upgrade from an older version? =

The plugin automatically handles database upgrades. Simply update the plugin through WordPress, and it will automatically upgrade your database schema if needed.

= Can I use this without WooCommerce? =

Yes! While WooCommerce integration provides additional features, the plugin works perfectly without it. You'll still get all the AI chat functionality and user empowerment tools.

== Screenshots ==

1. Chat widget on frontend
2. Admin settings page with model selection
3. Chat history dashboard
4. Conversation filtering options
5. User empowerment tools in action
6. WooCommerce integration features

== Changelog ==

= 1.0.0 =
* Initial release with comprehensive AI chatbot functionality
* OpenAI integration with multiple model support
* WooCommerce integration for e-commerce features
* Comprehensive chat history and admin dashboard
* User empowerment tools and conversation guidance
* Automatic user identification and guest tracking
* Dynamic function calling for intelligent data queries
* Security features and privacy protection
* Responsive design for all devices
* Automatic database upgrades and optimization

== Upgrade Notice ==

= 1.0.0 =
This is the initial release of AI Chatbot for WordPress. The plugin includes all core features and is ready for production use.

== Support ==

For support and questions:

1. Check the FAQ section above
2. Review the troubleshooting guide in the plugin documentation
3. Enable WordPress debugging for detailed error logs
4. Test with the provided test scripts
5. Verify all requirements are met

= Troubleshooting =

**Common Issues:**

* **API Key Issues**: Ensure your OpenAI API key is correct and has sufficient credits
* **WooCommerce Integration**: Verify WooCommerce is installed and active
* **Database Issues**: Run the automatic upgrade script if needed
* **Performance Issues**: Check your server's PHP memory limits

**Debug Information:**

Enable WordPress debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Look for log entries starting with "AIChatbot:" in `wp-content/debug.log`.

== Privacy Policy ==

This plugin collects and stores:
* User messages and AI responses
* User identification information (for logged-in users)
* IP addresses for security monitoring
* Session data for conversation tracking

All data is stored securely and can be managed through the WordPress admin interface. Consider implementing data retention policies and ensuring compliance with privacy regulations (GDPR, etc.).

== License ==

This plugin is licensed under the GPL v2 or later.

== Credits ==

* Built with OpenAI's GPT models
* Designed for WordPress and WooCommerce
* Follows WordPress coding standards and best practices