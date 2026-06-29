using System;
using MySql.Data.MySqlClient; 

namespace Progra3Card.Administrativo
{
    class Program
    {
        // uso del puerto 3307 -- evito conflicto con otra base
        private static string connectionString = "Server=localhost;Port=3306;Database=mi_banco_db;Uid=root;Pwd=root;";

        static void Main(string[] args)
        {
            bool salir = false;
            while (!salir)
            {
                Console.Clear();
                Console.WriteLine("========================================");
                Console.WriteLine("    SISTEMA ADMINISTRATIVO PROGRA3CARD   ");
                Console.WriteLine("========================================");
                Console.WriteLine("1. Emitir Nueva Tarjeta (Alta de Cliente)");
                Console.WriteLine("2. Listar Tarjetas");
                Console.WriteLine("3. Ver Detalle de una Tarjeta / Cliente");
                Console.WriteLine("4. Eliminar Tarjeta (Baja de Sistema)");
                Console.WriteLine("5. Emitir Nueva Liquidación Mensual");
                Console.WriteLine("6. Salir");
                Console.WriteLine("========================================");
                Console.Write("Seleccione una opción: ");

                switch (Console.ReadLine())
                {
                    case "1": MenuEmitirTarjeta(); break;
                    case "2": MenuListarTarjetas(); break;
                    case "3": MenuVerDetalleTarjeta(); break;
                    case "4": MenuEliminarTarjeta(); break;
                    case "5": MenuEmitirLiquidacion(); break;
                    case "6": salir = true; break;
                    default:
                        Console.WriteLine("Opción no válida. Presione una tecla para continuar...");
                        Console.ReadKey();
                        break;
                }
            }
        }
        
        static void MenuEmitirLiquidacion()
        {
            Console.Clear();
            Console.WriteLine("--- EMITIR NUEVA LIQUIDACIÓN MENSUAL ---");
            
            try
            {
                Console.Write("Número de Cuenta: ");
                int cuenta = Convert.ToInt32(Console.ReadLine());
                Console.Write("Período (YYYY-MM): ");
                string periodo = Console.ReadLine();
                Console.Write("Fecha de Vencimiento (YYYY-MM-DD): ");
                string vto = Console.ReadLine();
                Console.Write("Total a Pagar (ejemplo 25000.00): ");
                decimal total = Convert.ToDecimal(Console.ReadLine());
                Console.Write("Pago Mínimo: ");
                decimal minimo = Convert.ToDecimal(Console.ReadLine());

                using (MySqlConnection conn = new MySqlConnection(connectionString))
                {
                    conn.Open();
                    string sql = "INSERT INTO liquidaciones (num_cuenta, periodo, fecha_vencimiento, total_a_pagar, pago_minimo) VALUES (@cuenta, @periodo, @vto, @total, @min)";
                    using (MySqlCommand cmd = new MySqlCommand(sql, conn))
                    {
                        cmd.Parameters.AddWithValue("@cuenta", cuenta);
                        cmd.Parameters.AddWithValue("@periodo", periodo);
                        cmd.Parameters.AddWithValue("@vto", vto);
                        cmd.Parameters.AddWithValue("@total", total);
                        cmd.Parameters.AddWithValue("@min", minimo);
                        cmd.ExecuteNonQuery();
                    }
                    Console.WriteLine("\n¡Liquidación emitida con éxito! Impacto inmediato en la web del cliente.");
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"\nError al emitir liquidación: {ex.Message}");
            }

            Console.WriteLine("\nPresione una tecla para volver al menú...");
            Console.ReadKey();
        }

        static string generarTarjeta(string binInicial)
        {
            Random rnd = new Random();
            string tarjeta = binInicial;
            
            // Calculamos numeros restantes
            int longitudFaltante = 15 - tarjeta.Length;

            for (int i = 0; i < longitudFaltante; i++)
            {
                tarjeta += rnd.Next(0, 10).ToString();
            }

            // Calculamos la suma Mod 10 sobre esos 15 dígitos
            int suma = 0;
            for (int i = 0; i < 15; i++)
            {
                // Convertimos el char actual a entero
                int digito = int.Parse(tarjeta[i].ToString());

                // Las posiciones impares de la tarjeta se duplican
                if (i % 2 == 0)
                {
                    digito *= 2;
                    if (digito > 9)
                    {
                        digito -= 9;
                    }
                }
                suma += digito;
            }

            // calculamos el verificador
            int digitoVerificador = (10 - (suma % 10)) % 10;
            return tarjeta + digitoVerificador;
        }
        static void MenuEmitirTarjeta()
        {
            Console.Clear();
            Console.WriteLine("--- ALTA DE CLIENTE Y EMISIÓN DE TARJETA ---");
            
            try
            {
                Console.Write("DNI del Titular: ");
                string dni = Console.ReadLine();
                Console.Write("Nombre: ");
                string nombre = Console.ReadLine();
                Console.Write("Apellido: ");
                string apellido = Console.ReadLine();
                Console.Write("Email: ");
                string email = Console.ReadLine();
                
                Console.WriteLine("\nBancos Disponibles:");
                Console.WriteLine("1. Banco Galicia");
                Console.WriteLine("2. Banco Nación");
                Console.WriteLine("3. Banco Santander");
                Console.Write("Seleccione Banco (1-3): ");
                string opcionBanco = Console.ReadLine();
                
                string bancoSeleccionado = "";

                switch (opcionBanco)
                {
                    case "1": bancoSeleccionado = "Banco Galicia"; break;
                    case "2": bancoSeleccionado = "Banco Nación"; break;
                    case "3": bancoSeleccionado = "Banco Santander"; break;
                    default:
                        Console.WriteLine("Opción inválida. Se asignará Banco Nación por defecto.");
                        bancoSeleccionado = "Banco Nación";
                        break;
                }

                using (MySqlConnection conn = new MySqlConnection(connectionString))
                {
                    conn.Open();
                    
                    string numTarjetaGenerado = "";
                    bool tarjetaYaExiste = true;

                    // VALIDAMOS - iteramos hasta que ocnsigamos una tarjeta unica
                    do
                    {
                        numTarjetaGenerado = generarTarjeta("4512");

                        // Consultamos a la base si ya existe este número
                        string checkSql = "SELECT COUNT(*) FROM tarjetas WHERE numero_tarjeta = @num";
                        using (MySqlCommand checkCmd = new MySqlCommand(checkSql, conn))
                        {
                            checkCmd.Parameters.AddWithValue("@num", numTarjetaGenerado);
                            
                            // ExecuteScalar devuelve el valor de la primera columna de la primera fila (el COUNT)
                            int cantidad = Convert.ToInt32(checkCmd.ExecuteScalar());
                            
                            if (cantidad == 0)
                            {
                                // Si es 0 ==== la tarjeta está libre
                                tarjetaYaExiste = false;
                            }
                        }

                    } while (tarjetaYaExiste);      

                    // Insertamos al usuario
                    string sqlUser = "INSERT INTO usuarios (documento, tipo_doc, nombre, apellido, fecha_nacimiento, email, activo) VALUES (@dni, 'DNI', @nom, @ape, '1990-01-01', @email, 1)";
                    using (MySqlCommand cmdUser = new MySqlCommand(sqlUser, conn))
                    {
                        cmdUser.Parameters.AddWithValue("@dni", dni);
                        cmdUser.Parameters.AddWithValue("@nom", nombre);
                        cmdUser.Parameters.AddWithValue("@ape", apellido);
                        cmdUser.Parameters.AddWithValue("@email", email);
                        cmdUser.ExecuteNonQuery();
                    }

                    // Insertamos la tarjeta
                    string sqlCard = "INSERT INTO tarjetas (numero_tarjeta, banco_emisor, dni_titular) VALUES (@num, @banco, @dni)";
                    using (MySqlCommand cmdCard = new MySqlCommand(sqlCard, conn))
                    {
                        cmdCard.Parameters.AddWithValue("@num", numTarjetaGenerado);
                        cmdCard.Parameters.AddWithValue("@banco", bancoSeleccionado);
                        cmdCard.Parameters.AddWithValue("@dni", dni);
                        cmdCard.ExecuteNonQuery();
                    }
                    
                    Console.ForegroundColor = ConsoleColor.Green;
                    Console.WriteLine("\n¡Cliente registrado con éxito!");
                    
                    Console.WriteLine($"Se ha emitido la tarjeta única: {numTarjetaGenerado} ({bancoSeleccionado})");
                    Console.ResetColor();
                    Console.WriteLine("El cliente ya puede activar su cuenta en la plataforma web.");
                }
            }
            catch (MySqlException sqlEx)
            {
                // Exepciones como crear un usuario con un dni que no existe, o conflictos con la base
                Console.ForegroundColor = ConsoleColor.Red;
                Console.WriteLine($"\nError de Base de Datos: {sqlEx.Message}");
                Console.ResetColor();
            }
            catch (Exception ex)
            {
                Console.WriteLine($"\nError general: {ex.Message}");
            }

            Console.WriteLine("\nPresione una tecla para volver al menú...");
            Console.ReadKey();
        }

        static void MenuListarTarjetas()
        {
            Console.Clear();
            Console.WriteLine("--- LISTADO GENERAL DE TARJETAS ---");
            Console.WriteLine("{0,-12} {1,-18} {2,-20} {3,-15}", "Nro Cuenta", "Nro Tarjeta", "Banco Emisor", "DNI Titular");
            Console.WriteLine("----------------------------------------------------------------------");
      
            ObtenerYMostrarTarjetas();

            Console.WriteLine("\nPresione una tecla para volver al menú...");
            Console.ReadKey();
        }

        static void MenuVerDetalleTarjeta()
        {
            Console.Clear();
            Console.WriteLine("--- DETALLE DE TARJETA Y CLIENTE ---");
            Console.Write("Ingrese el Número de Cuenta a consultar: ");
            
            try
            {
                int numCuenta = Convert.ToInt32(Console.ReadLine());
                MostrarDetalleCompleto(numCuenta);
            }
            catch
            {
                Console.WriteLine("Número de cuenta inválido.");
            }

            Console.WriteLine("\nPresione una tecla para volver al menú...");
            Console.ReadKey();
        }


        static void MenuEliminarTarjeta()
        {
            Console.Clear();
            Console.WriteLine("--- ELIMINAR TARJETA DEL SISTEMA ---");
            Console.Write("Ingrese el Número de Cuenta de la tarjeta a dar de baja: ");
            int numCuenta = Convert.ToInt32(Console.ReadLine());

            Console.ForegroundColor = ConsoleColor.Red;
            Console.WriteLine("\n⚠️ ADVERTENCIA: Se eliminará la tarjeta, sus liquidaciones y los datos de acceso web vinculados.");
            Console.ResetColor();
            Console.Write("¿Está seguro de continuar? (S/N): ");
            
            if (Console.ReadLine().ToUpper() == "S")
            {
                bool exito = DarDeBajaTarjeta(numCuenta);

                if (exito)
                    Console.WriteLine("\nTarjeta eliminada correctamente del sistema.");
                else
                    Console.WriteLine("\nError al intentar eliminar la tarjeta. Verifique el número de cuenta.");
            }
            else
            {
                Console.WriteLine("\nOperación cancelada.");
            }

            Console.WriteLine("\nPresione una tecla para volver al menú...");
            Console.ReadKey();
        }


        // =========================================================================
        // MÉTODOS BASE QUE DEBEN COMPLETAR CON LA LÓGICA 
        // =========================================================================

        static void ObtenerYMostrarTarjetas()
        {
            using (MySqlConnection conn = new MySqlConnection(connectionString))
            {
                conn.Open();
                string query = "SELECT num_cuenta, numero_tarjeta, banco_emisor, dni_titular FROM tarjetas";
                
                using (MySqlCommand cmd = new MySqlCommand(query, conn))
                {
                    using (MySqlDataReader reader = cmd.ExecuteReader())
                    {
                        // Iteramos fila por fila
                        while (reader.Read())
                        {
                            Console.WriteLine("{0,-12} {1,-18} {2,-20} {3,-15}", 
                                reader["num_cuenta"], 
                                reader["numero_tarjeta"], 
                                reader["banco_emisor"], 
                                reader["dni_titular"]);
                        }
                    }
                }
            }
        }

        static void MostrarDetalleCompleto(int cuenta)
        {
            using (MySqlConnection conn = new MySqlConnection(connectionString))
            {
                conn.Open();
                // JOIN de tablas para cruzar en base al numero de cuenta la info del usuario
                string query = @"SELECT t.numero_tarjeta, t.banco_emisor, t.estado, t.saldo, 
                                        u.nombre, u.apellido, u.documento, u.email
                                 FROM tarjetas t
                                 INNER JOIN usuarios u ON t.dni_titular = u.documento
                                 WHERE t.num_cuenta = @cuenta";
                                 
                using (MySqlCommand cmd = new MySqlCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@cuenta", cuenta);
                    
                    using (MySqlDataReader reader = cmd.ExecuteReader())
                    {
                        if (reader.Read())
                        {
                            Console.WriteLine("\n--- DATOS DEL TITULAR ---");
                            Console.WriteLine($"Nombre Completo: {reader["nombre"]} {reader["apellido"]}");
                            Console.WriteLine($"Documento: {reader["documento"]}");
                            Console.WriteLine($"Email: {reader["email"]}");
                            
                            Console.WriteLine("\n--- DATOS DE LA TARJETA ---");
                            Console.WriteLine($"Número: {reader["numero_tarjeta"]}");
                            Console.WriteLine($"Banco Emisor: {reader["banco_emisor"]}");
                            Console.WriteLine($"Estado: {reader["estado"]}");
                            Console.WriteLine($"Saldo Actual: ${reader["saldo"]}");
                        }
                        else
                        {
                            Console.WriteLine("No se encontraron registros para ese Número de Cuenta.");
                        }
                    }
                }
            }
        }

        static bool DarDeBajaTarjeta(int cuenta)
        {
            using (MySqlConnection conn = new MySqlConnection(connectionString))
            {
                conn.Open();
                
                // OBTENER DNI antes de eliminar tarjeta
                string dniTitular = null;
                string getDniQuery = "SELECT dni_titular FROM tarjetas WHERE num_cuenta = @cuenta";
                
                using (MySqlCommand cmdDni = new MySqlCommand(getDniQuery, conn))
                {
                    cmdDni.Parameters.AddWithValue("@cuenta", cuenta);
                    var resultado = cmdDni.ExecuteScalar();
                    
                    // Si la tarjeta no existe === cortamos aca
                    if (resultado == null)
                    {
                        return false; 
                    }
                    dniTitular = resultado.ToString();
                }

                // Desactivamos al usuario
                string updateUsuarioQuery = "UPDATE usuarios SET activo = 0 WHERE documento = @dni";
                using (MySqlCommand cmdUpdate = new MySqlCommand(updateUsuarioQuery, conn))
                {
                    cmdUpdate.Parameters.AddWithValue("@dni", dniTitular);
                    cmdUpdate.ExecuteNonQuery();
                }

                // ahora si, borramos su tarjeta asociada con todas las liquidaciones (cascade)
                string deleteTarjetaQuery = "DELETE FROM tarjetas WHERE num_cuenta = @cuenta";
                using (MySqlCommand cmdDelete = new MySqlCommand(deleteTarjetaQuery, conn))
                {
                    cmdDelete.Parameters.AddWithValue("@cuenta", cuenta);
                    int filasAfectadas = cmdDelete.ExecuteNonQuery();
                    
                    // Si borro 1 o más filas === true)
                    return filasAfectadas > 0;
                }
            }
        }
    }
}