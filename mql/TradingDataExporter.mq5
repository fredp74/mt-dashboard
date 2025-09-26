//+------------------------------------------------------------------+
//| TradingDataExporter.mq5                                          |
//| Export trading data for web dashboard                            |
//+------------------------------------------------------------------+

#property version   "1.00"
#property description "Exports MT5 trading data to web server"

#include <Trade\PositionInfo.mqh>

input int UpdateIntervalSeconds = 60;              // Update interval in seconds
input string WebServerURL = "https://algotradingresearch.com/dashboard/api/receive_data.php";
input string APIKey = "your-secure-api-key-here";  // Your API key
input bool EnableWebRequests = true;               // Enable web requests
input bool EnableFileExport = true;                // Enable file export as backup
input string ExportFileName = "mt5_data.json";     // Export file name

// Global variables
datetime lastUpdate = 0;
int updateCounter = 0;

//+------------------------------------------------------------------+
//| Expert initialization function                                   |
//+------------------------------------------------------------------+
int OnInit()
{
    Print("MT5 Trading Data Exporter initialized");
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
    double balance = AccountInfoDouble(ACCOUNT_BALANCE);
    double equity = AccountInfoDouble(ACCOUNT_EQUITY);
    double profit = AccountInfoDouble(ACCOUNT_PROFIT);
    double margin = AccountInfoDouble(ACCOUNT_MARGIN);
    double freeMargin = AccountInfoDouble(ACCOUNT_MARGIN_FREE);
    
    // Position information
    int totalPositions = PositionsTotal();
    double totalVolume = 0;
    double totalProfit = 0;
    
    for(int i = 0; i < totalPositions; i++)
    {
        if(PositionSelectByIndex(i))
        {
            totalVolume += PositionGetDouble(POSITION_VOLUME);
            totalProfit += PositionGetDouble(POSITION_PROFIT);
        }
    }
    
    // Server time
    datetime serverTime = TimeCurrent();
    string timeString = TimeToString(serverTime, TIME_DATE|TIME_MINUTES|TIME_SECONDS);
    
    // Build JSON string
    string json = StringFormat(
        "{"
        "\"account_type\": \"MT5\","
        "\"balance\": %.2f,"
        "\"equity\": %.2f,"
        "\"profit\": %.2f,"
        "\"margin\": %.2f,"
        "\"free_margin\": %.2f,"
        "\"open_positions\": %d,"
        "\"total_volume\": %.2f,"
        "\"server_time\": \"%s\","
        "\"timestamp\": \"%s\","
        "\"account_number\": %d,"
        "\"account_leverage\": %d"
        "}",
        balance,
        equity,
        profit,
        margin,
        freeMargin,
        totalPositions,
        totalVolume,
        timeString,
        timeString,
        (int)AccountInfoInteger(ACCOUNT_LOGIN),
        (int)AccountInfoInteger(ACCOUNT_LEVERAGE)
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
    char postData[];
    string headers = "Content-Type: application/json\r\nX-API-Key: " + APIKey + "\r\n";
    
    // Convert string to char array
    StringToCharArray(jsonData, postData, 0, StringLen(jsonData));
    
    // Resize array to remove null terminator
    ArrayResize(postData, ArraySize(postData) - 1);
    
    // Send HTTP request
    char result[];
    string resultHeaders;
    
    int timeout = 5000; // 5 seconds timeout
    
    int res = WebRequest(
        "POST",
        WebServerURL,
        headers,
        timeout,
        postData,
        result,
        resultHeaders
    );
    
    if(res == 200)
    {
        string response = CharArrayToString(result);
        Print("Data sent successfully. Response: ", response);
    }
    else if(res == -1)
    {
        Print("WebRequest error: ", GetLastError());
        Print("Make sure the URL is added to allowed URLs in Tools -> Options -> Expert Advisors");
    }
    else
    {
        Print("HTTP Error: ", res);
        string response = CharArrayToString(result);
        Print("Response: ", response);
    }
}

//+------------------------------------------------------------------+
//| Expert deinitialization function                                 |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    Print("MT5 Trading Data Exporter stopped. Reason: ", reason);
}
