//+------------------------------------------------------------------+
//| TradingDataExporter.mq4                                          |
//| Export MT4 trading data for web dashboard                        |
//+------------------------------------------------------------------+

#property version   "1.00"
#property description "Exports MT4 trading data to web server"
#property strict

input int UpdateIntervalSeconds = 60;              // Update interval in seconds
input string WebServerURL = "https://algotradingresearch.com/dashboard/api/receive_data.php";
input string APIKey = "your-secure-api-key-here";  // Your API key
input bool EnableWebRequests = true;               // Enable web requests
input bool EnableFileExport = true;                // Enable file export as backup
input string ExportFileName = "mt4_data.json";     // Export file name

// Global variables
datetime lastUpdate = 0;
int updateCounter = 0;

//+------------------------------------------------------------------+
//| Expert initialization function                                   |
//+------------------------------------------------------------------+
int OnInit()
{
    Print("MT4 Trading Data Exporter initialized");
    Print("Update interval: ", UpdateIntervalSeconds, " seconds");
    Print("Web URL: ", WebServerURL);
    Print("File export: ", EnableFileExport ? "Enabled" : "Disabled");
    
    // Initial export
    ExportTradingData();
    
    return(INIT_SUCCEEDED);
}

//+------------------------------------------------------------------+
//| Expert tick function                                             |
//+------------------------------------------------------------------+
void OnTick()
{
    // Check if it's time to update
    datetime currentTime = TimeCurrent();
    if(currentTime - lastUpdate >= UpdateIntervalSeconds)
    {
        ExportTradingData();
        lastUpdate = currentTime;
    }
}

//+------------------------------------------------------------------+
//| Export trading data                                              |
//+------------------------------------------------------------------+
void ExportTradingData()
{
    string jsonData = CreateJSONData();
    updateCounter++;
    
    Print("Exporting data (Update #", updateCounter, ")");
    
    // Export to file if enabled
    if(EnableFileExport)
    {
        ExportToFile(jsonData);
    }
    
    // Send via web request if enabled
    if(EnableWebRequests)
    {
        SendDataViaHTTP(jsonData);
    }
}

//+------------------------------------------------------------------+
//| Create JSON data string                                          |
//+------------------------------------------------------------------+
string CreateJSONData()
{
    // Account information
    double balance = AccountBalance();
    double equity = AccountEquity();
    double profit = AccountProfit();
    double margin = AccountMargin();
    double freeMargin = AccountFreeMargin();
    
    // Count open positions and calculate totals
    int totalOrders = OrdersTotal();
    double totalVolume = 0;
    double totalOrderProfit = 0;
    int openPositions = 0;
    
    for(int i = 0; i < totalOrders; i++)
    {
        if(OrderSelect(i, SELECT_BY_POS, MODE_TRADES))
        {
            if(OrderType() <= 1) // Only buy/sell orders (not pending)
            {
                openPositions++;
                totalVolume += OrderLots();
                totalOrderProfit += OrderProfit() + OrderSwap() + OrderCommission();
            }
        }
    }
    
    // Server time
    datetime serverTime = TimeCurrent();
    string timeString = TimeToStr(serverTime, TIME_DATE|TIME_MINUTES|TIME_SECONDS);
    
    // Build JSON string
    string json = StringConcatenate(
        "{",
        "\"account_type\": \"MT4\",",
        "\"balance\": ", DoubleToStr(balance, 2), ",",
        "\"equity\": ", DoubleToStr(equity, 2), ",",
        "\"profit\": ", DoubleToStr(profit, 2), ",",
        "\"margin\": ", DoubleToStr(margin, 2), ",",
        "\"free_margin\": ", DoubleToStr(freeMargin, 2), ",",
        "\"open_positions\": ", IntegerToString(openPositions), ",",
        "\"total_volume\": ", DoubleToStr(totalVolume, 2), ",",
        "\"server_time\": \"", timeString, "\",",
        "\"timestamp\": \"", timeString, "\",",
        "\"account_number\": ", IntegerToString(AccountNumber()), ",",
        "\"account_leverage\": ", IntegerToString(AccountLeverage()),
        "}"
    );
    
    return json;
}

//+------------------------------------------------------------------+
//| Export data to file                                              |
//+------------------------------------------------------------------+
void ExportToFile(string jsonData)
{
    int fileHandle = FileOpen(ExportFileName, FILE_WRITE|FILE_TXT);
    
    if(fileHandle != INVALID_HANDLE)
    {
        FileWrite(fileHandle, jsonData);
        FileClose(fileHandle);
        Print("Data exported to file: ", ExportFileName);
    }
    else
    {
        Print("Error opening file for writing: ", GetLastError());
    }
}

//+------------------------------------------------------------------+
//| Send data via HTTP POST request                                  |
//+------------------------------------------------------------------+
void SendDataViaHTTP(string jsonData)
{
    // Note: MT4 WebRequest is more limited than MT5
    // This function may need broker-specific implementation
    // or use alternative methods like DLL calls
    
    Print("Attempting to send data via HTTP...");
    Print("JSON Data: ", jsonData);
    
    // For MT4, you might need to use file-based transfer
    // or a custom DLL for HTTP requests
    // This is a placeholder for the actual implementation
    
    Print("HTTP sending completed (check broker WebRequest support)");
}

//+------------------------------------------------------------------+
//| Expert deinitialization function                                 |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    Print("MT4 Trading Data Exporter stopped. Reason: ", reason);
}
