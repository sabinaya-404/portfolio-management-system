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
    $title = trim($title);
    if ($title === '' || mb_strlen($title) > 200) {
        return null;
    }

    $description = trim($description);
    if (mb_strlen($description) > 10000) {
        $description = mb_substr($description, 0, 10000);
    }

    $link = trim($link);
    if ($link === '' || !filter_var($link, FILTER_VALIDATE_URL) || !preg_match("/^https?:\/\//i", $link)) {
        return null;
    }

    if (mb_strlen($link) > 500) {
        return null;
    }

    $parsedDate = null;
    $formatsToTry = [
        'D, d M Y H:i:s O',
        'd M Y H:i:s O',
        'D, d M Y H:i:s T',
        'Y-m-d H:i:s',
        'Y-m-d',
        'd M Y',
        'M d, Y',
    ];

    foreach ($formatsToTry as $format) {
        $date = DateTime::createFromFormat($format, trim($pubDate));
        if ($date !== false && $date->format($format) === trim($pubDate)) {
            $parsedDate = $date->format('Y-m-d');
            break;
        }
    }

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
    $titleLower = mb_strtolower($title);
    $descLower = mb_strtolower(strip_tags($description));

    $financialTerms = [
        'share', 'shares', 'stock', 'stocks', 'market', 'nepse', 'ipo',
        'securities', 'trading', 'bull', 'index', 'dividend',
        'portfolio', 'investment', 'finance', 'financial',
        'earnings', 'results', 'profit', 'loss', 'turnover',
        'volume', 'bid', 'broker', 'demat',

        'शेयर', 'सेयर', 'स्टक', 'मार्केट', 'नेप्से', 'आईपीओ', 'सिक्युरिटी',
        'ट्रेडिङ', 'इन्डेक्स', 'डिभिडेन्ड', 'पोर्टफोलियो', 'निवेश',
        'वित्तीय', 'कॉर्पोरेट', 'अर्निंग', 'परिणाम', 'नाफा',
        'नोक्सान', 'टर्नओवर', 'वोल्युम', 'बिड', 'आस्क', 'ब्रोकर',
        'डेमाट'
    ];

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

    libxml_use_internal_errors(true);

    $xmlContentToParse = $xmlContent;
    $rssEndPos = strpos($xmlContent, '</rss>');
    $feedEndPos = strpos($xmlContent, '</feed>');

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

    foreach ($items as $item) {
        $results['items_processed']++;

        $title = isset($item->title) ? (string)$item->title : '';
        $description = isset($item->description) ? (string)$item->description : '';
        $link = isset($item->link) ? (string)$item->link : '';
        $pubDate = isset($item->pubDate) ? (string)$item->pubDate : '';

        $validated = validateRssItem($title, $description, $link, $pubDate);
        if ($validated === null) {
            $results['errors'][] = "Invalid item data (missing/invalid date) in feed {$feedUrl}";
            continue;
        }

        if (!isFinanciallyRelevant($validated['title'], $validated['description'])) {
            $results['skipped']++;
            continue;
        }

        $checkStmt = $conn->prepare(
            "SELECT id FROM ipo_news WHERE source_url = ? LIMIT 1"
        );
        $checkStmt->bind_param("s", $validated['link']);

        if ($checkStmt->execute()) {
            $result = $checkStmt->get_result();
            if ($result->num_rows > 0) {
                $results['skipped']++;
            } else {
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
                    error_log("Database error inserting RSS item: " . $insertStmt->error);
                    $results['errors'][] = "Database error processing item.";
                }
                $insertStmt->close();
            }
        } else {
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

    if ($cachedUserId !== null) {
        return $cachedUserId;
    }

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
    return null;
}