<?php
/**
 * RSS Import Functions for Portfolio Management System
 * Handles fetching, parsing, and importing RSS feeds into the ipo_news table
 */

if (!defined('INCLUDES_RSS_IMPORT')) {
    define('INCLUDES_RSS_IMPORT', true);
}

/**
 * Validate and sanitize RSS item data
 *
 * @param string $title Item title
 * @param string $description Item description/content
 * @param string $link Item URL
 * @param string $pubDate Item publication date
 * @return array|null Sanitized data or null if invalid
 */
function validateRssItem(string $title, string $description, string $link, string $pubDate) {
    // Validate title
    $title = trim($title);
    if ($title === '' || mb_strlen($title) > 200) {
        return null;
    }

    // Validate description (can be empty but limit length)
    $description = trim($description);
    if (mb_strlen($description) > 10000) { // Reasonable limit for content
        $description = mb_substr($description, 0, 10000);
    }

    // Validate link
    $link = trim($link);
    if ($link === '' || !filter_var($link, FILTER_VALIDATE_URL) || !preg_match("/^https?:\/\//i", $link)) {
        return null;
    }

    if (mb_strlen($link) > 500) {
        return null;
    }

    // Validate and parse publication date
    $parsedDate = null;
    $formatsToTry = [
        'D, d M Y H:i:s O',      // RFC 822 (Wed, 02 Oct 2002 08:00:00 EST)
        'd M Y H:i:s O',         // Without day of week
        'D, d M Y H:i:s T',      // RFC 822 with timezone abbrev
        'Y-m-d H:i:s',           // ISO-like
        'Y-m-d',                 // Date only
        'd M Y',                 // 02 Oct 2002
        'M d, Y',                // Oct 02, 2002
    ];

    foreach ($formatsToTry as $format) {
        $date = DateTime::createFromFormat($format, trim($pubDate));
        if ($date !== false && $date->format($format) === trim($pubDate)) {
            $parsedDate = $date->format('Y-m-d');
            break;
        }
    }

    // If we couldn't parse the date, return null to skip this item
    if ($parsedDate === null) {
        return null;
    }

    return [
        'title' => $title,
        'description' => $description,
        'link' => $link,
        'pubDate' => $parsedDate
    ];
}

/**
 * Check if an RSS item is financially relevant based on title and description
 *
 * @param string $title Item title
 * @param string $description Item description/content
 * @return bool True if financially relevant, false otherwise
 */
function isFinanciallyRelevant(string $title, string $description): bool {
    // Convert to lowercase for case-insensitive matching; strip HTML tags from description
    $titleLower = mb_strtolower($title);
    $descLower = mb_strtolower(strip_tags($description));

    // Key financial terms in English and Nepali (Devanagari)
    $financialTerms = [
        // English terms
        'share', 'shares', 'stock', 'stocks', 'market', 'nepse', 'ipo',
        'securities', 'trading', 'bull', 'index', 'dividend',
        'portfolio', 'investment', 'finance', 'financial',
        'earnings', 'results', 'profit', 'loss', 'turnover',
        'volume', 'bid', 'broker', 'demat',

        // Nepali terms (Devanagari)
        'शेयर', 'सेयर', 'स्टक', 'मार्केट', 'नेप्से', 'आईपीओ', 'सिक्युरिटी',
        'ट्रेडिङ', 'इन्डेक्स', 'डिभिडेन्ड', 'पोर्टफोलियो', 'निवेश',
        'वित्तीय', 'कॉर्पोरेट', 'अर्निंग', 'परिणाम', 'नाफा',
        'नोक्सान', 'टर्नओवर', 'वोल्युम', 'बिड', 'आस्क', 'ब्रोकर',
        'डेमाट'
    ];

    // Check each term in title or description
    foreach ($financialTerms as $term) {
        if (mb_strpos($titleLower, $term) !== false ||
            mb_strpos($descLower, $term) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Import items from an RSS feed
 *
 * @param mysqli $conn Database connection
 * @param string $feedUrl URL of the RSS feed to import
 * @param int $adminUserId ID of the admin user triggering the import
 * @return array Results containing counts and errors
 */
function importRssFeed(mysqli $conn, string $feedUrl, int $adminUserId) {
    $results = [
        'feed_url' => $feedUrl,
        'imported' => 0,
        'skipped' => 0,
        'errors' => [],
        'items_processed' => 0
    ];

    // Fetch RSS feed content with cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $feedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Portfolio Management System RSS Importer/1.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $xmlContent = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($xmlContent === false || $httpCode >= 400) {
        $errorMsg = "Failed to fetch RSS feed: {$feedUrl}";
        if ($curlError) {
            $errorMsg .= " ({$curlError})";
        } elseif ($httpCode >= 400) {
            $errorMsg .= " (HTTP {$httpCode})";
        }
        $results['errors'][] = $errorMsg;
        return $results;
    }

    // Parse XML
    libxml_use_internal_errors(true);

    // Extract valid XML part if there's extra content (e.g., Cloudflare beacon)
    $xmlContentToParse = $xmlContent;
    $rssEndPos = strpos($xmlContent, '</rss>');
    $feedEndPos = strpos($xmlContent, '</feed>');

    // Find the earliest ending position
    $endPos = PHP_INT_MAX;
    $foundEnd = false;

    if ($rssEndPos !== false) {
        $endPos = min($endPos, $rssEndPos + strlen('</rss>'));
        $foundEnd = true;
    }
    if ($feedEndPos !== false) {
        $endPos = min($endPos, $feedEndPos + strlen('</feed>'));
        $foundEnd = true;
    }

    if ($foundEnd && $endPos < strlen($xmlContent)) {
        // Extract just the XML part
        $xmlContentToParse = substr($xmlContent, 0, $endPos);
    }

    $xml = simplexml_load_string($xmlContentToParse);
    if ($xml === false) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        $errorMsg = "Failed to parse RSS feed: {$feedUrl}";
        foreach ($errors as $error) {
            $errorMsg .= " ({$error->message})";
        }
        $results['errors'][] = $errorMsg;
        return $results;
    }
    libxml_clear_errors();

    // Find items - handle different RSS structures
    $items = null;
    if (isset($xml->channel)) {
        if (isset($xml->channel->item)) {
            $items = $xml->channel->item;
        }
    } elseif (isset($xml->item)) {
        $items = $xml->item;
    }

    if ($items === null) {
        $results['errors'][] = "No items found in RSS feed: {$feedUrl}";
        return $results;
    }

    // Process each item
    foreach ($items as $item) {
        $results['items_processed']++;

        // Extract item data
        $title = isset($item->title) ? (string)$item->title : '';
        $description = isset($item->description) ? (string)$item->description : '';
        $link = isset($item->link) ? (string)$item->link : '';
        $pubDate = isset($item->pubDate) ? (string)$item->pubDate : '';

        // Validate item data
        $validated = validateRssItem($title, $description, $link, $pubDate);
        if ($validated === null) {
            $results['errors'][] = "Invalid item data (missing/invalid date) in feed {$feedUrl}";
            continue;
        }

        // Check if item is financially relevant
        if (!isFinanciallyRelevant($validated['title'], $validated['description'])) {
            $results['skipped']++; // Count as skipped (irrelevant content)
            continue;
        }

        // Check for duplicate: same source_url (regardless of feed)
        $checkStmt = $conn->prepare(
            "SELECT id FROM ipo_news WHERE source_url = ? LIMIT 1"
        );
        $checkStmt->bind_param("s", $validated['link']);

        if ($checkStmt->execute()) {
            $result = $checkStmt->get_result();
            if ($result->num_rows > 0) {
                // Duplicate found - skip
                $results['skipped']++;
            } else {
                // No duplicate - insert new item
                $type = 'news';
                $title = $validated['title'];
                $description = $validated['description'];
                $publicationDate = $validated['pubDate'];
                $sourceUrl = $validated['link'];

                $insertStmt = $conn->prepare(
                    "INSERT INTO ipo_news (created_by, type, title, content, publication_date, source_url, rss_feed_url, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'published')"
                );
                $insertStmt->bind_param(
                    "issssss",
                    $adminUserId,
                    $type,
                    $title,
                    $description,
                    $publicationDate,
                    $sourceUrl,
                    $feedUrl
                );

                if ($insertStmt->execute()) {
                    $results['imported']++;
                } else {
                    // Log detailed error but show generic message to user
                    error_log("Database error inserting RSS item: " . $insertStmt->error);
                    $results['errors'][] = "Database error processing item.";
                }
                $insertStmt->close();
            }
        } else {
            // Log detailed error but show generic message to user
            error_log("Database error checking for duplicates: " . $checkStmt->error);
            $results['errors'][] = "Database error checking for duplicates.";
        }
        $checkStmt->close();
    }

    return $results;
}

/**
 * Get list of configured RSS feeds
 * In a future enhancement, this could come from a database table or config file
 *
 * @return array List of RSS feed URLs
 */
function getConfiguredRssFeeds() {
    // For now, hardcode the initial feed as specified in the requirements
    // In future versions, this could be loaded from:
    // - A database table (rss_feeds)
    // - A configuration file
    // - The admin interface

    return [
        'https://www.setopati.com/feed',
        'https://www.ratopati.com/feed'
    ];
}

/**
 * Get the system user ID for RSS imports
 * Returns the ID of an admin user to use as the creator for imported RSS items
 *
 * @param mysqli $conn Database connection
 * @return int|null Admin user ID or null if none found
 */
function getRssSystemUserId(mysqli $conn) {
    static $cachedUserId = null;

    // Return cached value if available
    if ($cachedUserId !== null) {
        return $cachedUserId;
    }

    // Query for an admin user
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if ($stmt === false) {
        error_log("Failed to prepare statement for getRssSystemUserId: " . $conn->error);
        return null;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cachedUserId = (int)$row['id'];
        $stmt->close();
        return $cachedUserId;
    }

    $stmt->close();
    return null; // No admin user found
}